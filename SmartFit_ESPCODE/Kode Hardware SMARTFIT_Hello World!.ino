#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <Wire.h>
#include <Adafruit_MPU6050.h>
#include <Adafruit_Sensor.h>
#include <ragasehat_inferencing.h>

// Wi-Fi credentials
const char* ssid = "zaki";
const char* password = "ojokdibagi";

// MQTT Broker settings
const char* mqtt_server = "9ee69f62df75483cae0f110171a4b368.s1.eu.hivemq.cloud";
const int mqtt_port = 8883;
const char* mqtt_user = "Buah_321";
const char* mqtt_password = "Buah_321";
const char* mqtt_client_id = "ESP32_Client";
const char* mqtt_topic = "iot/web/test/movement_type";
const char* device_id_topic = "iot/web/test/device_id";

// Device ID
const char* device_id = "SMARTFIT-12345";

// MPU-6050 object
Adafruit_MPU6050 mpu;

// Wi-Fi and MQTT clients
WiFiClientSecure espClient;
PubSubClient client(espClient);

// Buffer for Edge Impulse features
float features[EI_CLASSIFIER_DSP_INPUT_FRAME_SIZE];

// Timing variables
unsigned long lastPublish = 0;
const unsigned long publishInterval = 3000; // Publish movement every 3 seconds
unsigned long lastDeviceIdPublish = 0;
const unsigned long deviceIdPublishInterval = 10000; // Publish device ID every 10 seconds
const unsigned long sampleInterval = 20; // Sampling interval for 50Hz (1000ms / 50 = 20ms)

// Function to provide data to Edge Impulse
int raw_feature_get_data(size_t offset, size_t length, float *out_ptr) {
  memcpy(out_ptr, features + offset, length * sizeof(float));
  return 0;
}

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("Starting ESP32...");

  // Print Device ID
  Serial.print("Device ID: ");
  Serial.println(device_id);

  // Initialize I2C with custom pins (SDA: 9, SCL: 8)
  Wire.begin(9, 8);
  Serial.println("I2C Initialized");

  // Run I2C Scanner to detect devices
  runI2CScanner();

  // Initialize MPU-6050
  if (!mpu.begin(0x68, &Wire)) {
    Serial.println("Failed to find MPU6050 chip at address 0x68");
    // Try alternative address (0x69)
    if (!mpu.begin(0x69, &Wire)) {
      Serial.println("Failed to find MPU6050 chip at address 0x69");
      Serial.println("Check wiring or replace MPU-6050 module!");
      while (1) delay(10);
    } else {
      Serial.println("MPU6050 Found at address 0x69!");
    }
  } else {
    Serial.println("MPU6050 Found at address 0x68!");
  }

  // Configure MPU-6050
  mpu.setAccelerometerRange(MPU6050_RANGE_8_G);
  mpu.setGyroRange(MPU6050_RANGE_500_DEG);
  mpu.setFilterBandwidth(MPU6050_BAND_21_HZ);
  Serial.println("MPU6050 Configured");

  // Connect to Wi-Fi
  connectWiFi();

  // Configure MQTT
  espClient.setInsecure(); // Skip certificate verification (for testing)
  client.setServer(mqtt_server, mqtt_port);

  // Connect to MQTT
  connectMQTT();
}

void loop() {
  // Maintain MQTT connection
  if (!client.connected()) {
    connectMQTT();
  }
  client.loop();

  // Publish device ID periodically
  if (millis() - lastDeviceIdPublish >= deviceIdPublishInterval) {
    if (client.connected()) {
      client.publish(device_id_topic, device_id);
      Serial.print("Published Device ID: ");
      Serial.println(device_id);
      lastDeviceIdPublish = millis();
    }
  }

  // Collect MPU-6050 data at 50Hz
  static size_t featureIdx = 0;
  static unsigned long lastSampleTime = 0;

  if (millis() - lastSampleTime >= sampleInterval) {
    lastSampleTime = millis();

    sensors_event_t accel, gyro, temp;
    mpu.getEvent(&accel, &gyro, &temp);

    if (featureIdx < EI_CLASSIFIER_DSP_INPUT_FRAME_SIZE) {
      features[featureIdx++] = accel.acceleration.x;
      features[featureIdx++] = accel.acceleration.y;
      features[featureIdx++] = accel.acceleration.z;
      // features[featureIdx++] = gyro.gyro.x;
      // features[featureIdx++] = gyro.gyro.y;
      // features[featureIdx++] = gyro.gyro.z;
    }

    // Run classifier when buffer is full
    if (featureIdx >= EI_CLASSIFIER_DSP_INPUT_FRAME_SIZE) {
      featureIdx = 0; // Reset for next batch

      // Run Edge Impulse classifier
      ei_impulse_result_t result = {0};
      signal_t signal;
      signal.total_length = EI_CLASSIFIER_DSP_INPUT_FRAME_SIZE;
      signal.get_data = &raw_feature_get_data;

      EI_IMPULSE_ERROR res = run_classifier(&signal, &result, false);
      if (res != EI_IMPULSE_OK) {
        Serial.print("Classification error: ");
        Serial.println(res);
        return;
      }

      // Find label with highest confidence
      float bestValue = 0.0;
      String bestLabel = "";
      for (size_t ix = 0; ix < EI_CLASSIFIER_LABEL_COUNT; ix++) {
        if (result.classification[ix].value > bestValue) {
          bestValue = result.classification[ix].value;
          bestLabel = result.classification[ix].label;
        }
      }

      Serial.print("Classification: ");
      Serial.print(bestLabel);
      Serial.print(" (");
      Serial.print(bestValue * 100.0);
      Serial.println("%)");

      // Publish if confidence is high enough
      if (millis() - lastPublish >= publishInterval && bestValue > 0.6) {
        if (bestLabel == "pushup") {
          client.publish(mqtt_topic, "pushup");
          Serial.println("Published: pushup");
        } else if (bestLabel == "situp") {
          client.publish(mqtt_topic, "situp");
          Serial.println("Published: situp");
        } else if (bestLabel == "squatjump") {
          client.publish(mqtt_topic, "squatjump");
          Serial.println("Published: squatjump");
        }
        lastPublish = millis();
      }
    }
  }

  // Small delay to avoid overwhelming the loop
  delay(10);
}

void connectWiFi() {
  Serial.print("Connecting to Wi-Fi: ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWi-Fi connected!");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nFailed to connect to Wi-Fi. Restarting...");
    ESP.restart();
  }
}

void connectMQTT() {
  Serial.print("Connecting to MQTT broker: ");
  Serial.println(mqtt_server);
  int attempts = 0;
  while (!client.connected() && attempts < 3) {
    if (client.connect(mqtt_client_id, mqtt_user, mqtt_password)) {
      Serial.println("MQTT connected!");
      client.publish(device_id_topic, device_id);
      Serial.print("Published Device ID: ");
      Serial.println(device_id);
    } else {
      Serial.print("MQTT connection failed, rc=");
      Serial.print(client.state());
      Serial.println(" Retrying in 5 seconds...");
      delay(5000);
      attempts++;
    }
  }
  if (!client.connected()) {
    Serial.println("MQTT connection failed. Continuing without MQTT...");
  }
}

void runI2CScanner() {
  Serial.println("Scanning I2C bus...");
  byte error, address;
  int nDevices = 0;
  for (address = 1; address < 127; address++) {
    Wire.beginTransmission(address);
    error = Wire.endTransmission();
    if (error == 0) {
      Serial.print("I2C device found at address 0x");
      if (address < 16) Serial.print("0");
      Serial.println(address, HEX);
      nDevices++;
    }
  }
  if (nDevices == 0) {
    Serial.println("No I2C devices found!");
  } else {
    Serial.println("I2C scan complete.");
  }
}
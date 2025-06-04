<?php
   // Tambahkan header keamanan
   header('X-Frame-Options: DENY');
   header('X-Content-Type-Options: nosniff');
   header('Content-Security-Policy: default-src \'self\'; frame-src https://www.youtube.com;');
   ?>
   <!DOCTYPE html>
   <html lang="en">
   <head>
       <meta charset="UTF-8">
       <meta name="viewport" content="width=device-width, initial-scale=1.0">
       <title>Never Gonna Give You Up</title>
       <style>
           body {
               margin: 0;
               padding: 0;
               background: #000;
               display: flex;
               justify-content: center;
               align-items: center;
               height: 100vh;
               overflow: hidden;
           }
           iframe {
               width: 100%;
               height: 100%;
               border: none;
           }
       </style>
   </head>
   <body>
       <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&mute=0&controls=0&loop=1&playlist=dQw4w9WgXcQ" 
               allow="autoplay; encrypted-media" 
               allowfullscreen></iframe>
       <script>
           // Coba putar ulang jika autoplay gagal
           window.onload = function() {
               const iframe = document.querySelector('iframe');
               iframe.src = iframe.src; // Refresh iframe untuk memicu autoplay
           };
       </script>
   </body>
   </html>
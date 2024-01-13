<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f4f4f4;
    }

    .container {
      max-width: 600px;
      margin: 0 auto;
      padding: 20px;
      background-color: #fff;
      text-align: center; /* Center the content in the container */
    }

    h2 {
      color: #333;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    table, th, td {
      border: 1px solid #ddd;
    }

    th, td {
      padding: 10px;
      text-align: left;
    }

    th {
      background-color: #f2f2f2;
    }

    .footer {
      margin-top: 20px;
      color: #777;
      text-align: center; /* Center the content in the footer */
    }

    /* Center the image in the header */
    .header-image {
      display: block;
      margin: 0 auto;
    }
  </style>
</head>
<body>
  <div class="container">
    {{-- public/storage/images/logonew.png --}}
    <img src="{{asset('public/storage/images/logonew.png')}}" alt="Logo" class="header-image">
    <h2>Delete Account Request</h2>
    <p>Mr. Narayan Paswan has sent a request to delete the account.</p>
    
    <table>
      <tr>
        <th>User Id</th>
        <th>Email</th>
        <th>Mobile No</th>
      </tr>
      <tr>
        <td>8</td>
        <td>narayan@gmail.com</td>
        <td>9832339909</td>
      </tr>
    </table>
    
    <p><br>Thanks,<br>The Installers Team</p>
  </div>

  <div class="footer">
    <p>
      Copyright © 2024 <a href="https://theinstallers.in/" target="_blank">Innovate Installers Services private Limited</a> | 
      Designed by - <a href="https://worksaar.com/" target="_blank">WorkSaar</a>
    </p>
  </div>
</body>
</html>

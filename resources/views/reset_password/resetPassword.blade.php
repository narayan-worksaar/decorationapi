<!DOCTYPE html>
<html>
<head>
<title>Reset-Password</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        text-align: center;
    }

    h1 {
        font-size: 24px;
        margin-bottom: 20px;
    }

    form {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 300px;
        border: 1px solid #ccc;
        padding: 20px;
        border-radius: 5px;
        background-color: #f2f2f2;
    }

    input[type="password"] {
        padding: 10px;
        margin-bottom: 10px;
        width: 100%;
    }

    input[type="submit"] {
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        border: none;
        cursor: pointer;
    }

    input[type="submit"]:hover {
        background-color: #45a049;
    }

    p {
        color: red;
        margin: 5px 0;
    }
</style>
</head>
<body>
    <form action="{{ route('save_new_password') }}" method="POST">
        <h1>Reset Password</h1>    
        @if ($errors->any())
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p> 
        @endforeach
    @endif
        @csrf
        <input type="hidden" name="id" value="{{ $user[0]['id'] }}">
        <input type="password" name="password" placeholder="Enter password">
        <br>
        <input type="password" name="password_confirmation" placeholder="Enter confirm password">
        <br>
        <input type="submit" value="Reset Password">
    </form>
</body>
</html>

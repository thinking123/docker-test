<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="google-signin-client_id" content="{{ config('app.google_client_id') }}">
    <title>Login</title>
</head>
<body>
<div class="g-signin2" data-onsuccess="onSignIn"></div>

<script>
    function onSignIn(googleUser) {
        console.log("Google id_token: " + googleUser.Zi.id_token);
    }
</script>
<script src="https://apis.google.com/js/platform.js" async defer></script>
</body>
</html>
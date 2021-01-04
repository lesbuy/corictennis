<html manifest="uuid.appcache">

<head>
    <meta charset="UTF-8">
    <title>uuid</title>
</head>

<body>
    <script>
        const h5uuid = "{{ getIP() . "_" . date('Y-m-d_H:i:s', time()) }}";
        window.parent.window.passUuid(h5uuid);
    </script>
</body>

</html>

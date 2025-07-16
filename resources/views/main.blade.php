<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <div style="margin-top: 100px; width: 50%">
            <script src="https://unpkg.com/@vkid/sdk@<3.0.0/dist-sdk/umd/index.js"></script>
            <script type="text/javascript">
                if ('VKIDSDK' in window) {
                    const VKID = window.VKIDSDK;

                    VKID.Config.init({
                        app: 53915902,
                        redirectUrl: 'https://www.ping-check.ru',
                        responseMode: VKID.ConfigResponseMode.Callback,
                        source: VKID.ConfigSource.LOWCODE,
                        scope: '', // Заполните нужными доступами по необходимости
                    });

                    const oneTap = new VKID.OneTap();

                    oneTap.render({
                        container: document.currentScript.parentElement,
                        showAlternativeLogin: true
                    })
                        .on(VKID.WidgetEvents.ERROR, vkidOnError)
                        .on(VKID.OneTapInternalEvents.LOGIN_SUCCESS, function (payload) {
                            const code = payload.code;
                            const deviceId = payload.device_id;

                            VKID.Auth.exchangeCode(code, deviceId)
                                .then(vkidOnSuccess)
                                .catch(vkidOnError);
                        });

                    function vkidOnSuccess(data) {
                        // Обработка полученного результата
                    }

                    function vkidOnError(error) {
                        // Обработка ошибки
                    }
                }
            </script>
        </div>
</body>
</html>

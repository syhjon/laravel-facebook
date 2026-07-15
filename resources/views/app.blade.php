<!DOCTYPE html>
<html lang="zh-Hant-TW">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        <style>
            :root {
                --brand-primary: {{ $appData['project']['theme']['primary'] }};
                --brand-primary-rgb: {{ $appData['project']['theme']['primary_rgb'] }};
                --brand-primary-hover: {{ $appData['project']['theme']['primary_hover'] }};
                --brand-primary-active: {{ $appData['project']['theme']['primary_active'] }};
                --brand-primary-text: {{ $appData['project']['theme']['primary_text'] }};
                --brand-primary-subtle: {{ $appData['project']['theme']['primary_subtle'] }};
            }
        </style>

        @vite('resources/js/app.js')
    </head>
    <body>
        <div id="app"></div>

        <script>
            window.appData = {{ Illuminate\Support\Js::from($appData) }};
        </script>
    </body>
</html>

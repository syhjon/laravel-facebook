<!DOCTYPE html>
<html lang="zh-Hant-TW">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        <style>
            :root {
                --brand-primary: {{ $applicationData['project']['theme']['primary'] }};
                --brand-primary-rgb: {{ $applicationData['project']['theme']['primary_rgb'] }};
                --brand-primary-hover: {{ $applicationData['project']['theme']['primary_hover'] }};
                --brand-primary-active: {{ $applicationData['project']['theme']['primary_active'] }};
                --brand-primary-text: {{ $applicationData['project']['theme']['primary_text'] }};
                --brand-primary-subtle: {{ $applicationData['project']['theme']['primary_subtle'] }};
            }
        </style>

        @vite('resources/js/app.js')
    </head>
    <body>
        <div id="app"></div>

        <script>
            window.applicationData = {{ Illuminate\Support\Js::from($applicationData) }};
        </script>
    </body>
</html>

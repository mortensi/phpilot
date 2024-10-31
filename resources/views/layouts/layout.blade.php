<!DOCTYPE HTML>
<html>
<head> 
    <title>phpilot</title> 
	<link rel="shortcut icon" href="{{ asset('images/favicon-32x32.png') }}" type="image/x-icon" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
	<link rel="stylesheet" href="{{ asset('css/loading.css') }}">
	<link rel="stylesheet" href="{{ asset('css/bubble.css') }}">
	<link rel="stylesheet" href="{{ asset('css/style.css') }}">
	<meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/notify/0.4.2/notify.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/showdown/2.1.0/showdown.min.js" integrity="sha512-LhccdVNGe2QMEfI3x4DVV3ckMRe36TfydKss6mJpdHjNFiV07dFpS2xzeZedptKZrwxfICJpez09iNioiSZ3hA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('js/bubble.js') }}"></script>
    <script src="{{ asset('js/phpilot.js') }}"></script>
</head>
<body>
    <div id="wrapper">
        <section>
			@include('includes.header')
            <div class="container p-5 mt-6 mb-6">
				@yield('content')
				@yield('footer')
            </div>
        </section>
    </div>
</body>
</html>

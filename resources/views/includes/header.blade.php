<!-- resources/views/includes/header.blade.php -->
<nav class="p-1 mb-2 navbar is-transparent is-fixed-top" role="navigation" aria-label="main navigation">
    <div class="navbar-brand mr-6">
        <a href="{{ url('/') }}" class="navbar-item" style="width:102px;color:rgb(213 45 31);">
            <img src="{{ asset('images/phpilot.png') }}" alt="Logo">
        </a>
        <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" style="margin:0 0 0 auto">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </a>
    </div>

    <div id="navbarBasicExample" class="navbar-menu is-expanded">
        <div class="navbar-start" style="flex: 0 0 50%;">
            <a href="{{ url('/') }}" class="navbar-item">
                <span style="padding:6px;">chat</span>
            </a>
            <a href="{{ url('/data') }}" class="navbar-item">
                <span style="padding:6px;">data</span>
            </a>
            <a href="{{ url('/prompt') }}" class="navbar-item">
                <span style="padding:6px;">prompts</span>
            </a>
            <a href="{{ url('/cache') }}" class="navbar-item">
                <span style="padding:6px;">cache</span>
            </a>
            <a href="{{ url('/logger') }}" class="navbar-item">
                <span style="padding:6px;">logger</span>
            </a>
        </div>

        <div class="navbar-end" style="flex: 0 0 15%;">
        </div>
    </div>
</nav>
<script>
    $(document).ready(function() {
        // Check for click events on the navbar burger icon
        $(".navbar-burger").click(function() {
            // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
            $(".navbar-burger").toggleClass("is-active");
            $(".navbar-menu").toggleClass("is-active");
        });
    });
</script>

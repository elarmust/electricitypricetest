<!DOCTYPE html>
<html lang="et">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Spotihinnad</title>
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    </head>
    <body>
        <main class="container">
            <h1>Päev-ette hinnad</h1>
            @if(session('status'))
                <p class="success">{{ session('status') }}</p>
            @endif
            @if(session('error'))
                <p class="error">{{ session('error') }}</p>
            @endif
            <form method="get" class="controls" id="controls">
                <label>Kuupäev
                    <input type="date" name="date" id="date" value="{{ $selectedDate }}">
                </label>
                <label>Akna pikkus (h)
                    <select name="window" id="window">
                        @for($h = 1; $h <= 6; $h++)
                            <option value="{{ $h }}" @selected($h === $windowHours)>{{ $h }}</option>
                        @endfor
                    </select>
                </label>
                <button type="submit">Uuenda</button>
            </form>
            <section class="cards" id="cards"></section>
            <section class="windows" id="windows"></section>
            <section class="chart-wrap">
                <canvas id="priceChart" height="160"></canvas>
                <p class="error" id="chart-message" hidden></p>
            </section>
            <section id="prices-table" hidden>
                <button type="button" class="table-toggle" data-target="prices-table-body" aria-expanded="false">
                    <span class="arrow">&#9656;</span> Perioodide hinnad
                </button>
                <div class="table-wrap" id="prices-table-body" hidden>
                    <table class="prices">
                        <thead>
                            <tr><th>Algus (Tallinn)</th><th>€/MWh (spot)</th><th>snt/kWh (ilma KM)</th><th>snt/kWh (KM-ga)</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="table-toggle table-toggle-close" data-target="prices-table-body" aria-expanded="false" hidden>
                    <span class="arrow">&#9656;</span> Perioodide hinnad
                </button>
            </section>
            <section class="submit" id="submit">
                <h2>Saada tulemus</h2>
                <div class="success" id="submit-status" hidden></div>
                <div class="error" id="submit-error" hidden></div>
                <form method="post" action="{{ route('api.submissions') }}" id="submitForm">
                    @csrf
                    <input type="hidden" name="date" id="submit-date" value="{{ $selectedDate }}">
                    <input type="hidden" name="window" id="submit-window" value="{{ $windowHours }}">
                    <label>Nimi
                        <input type="text" name="name" value="{{ old('name') }}">
                        <span class="error" id="err-name" hidden></span>
                    </label>
                    <label>E-post
                        <input type="email" name="email" value="{{ old('email') }}">
                        <span class="error" id="err-email" hidden></span>
                    </label>
                    <label>Telefon
                        <input type="tel" name="phone" value="{{ old('phone') }}">
                        <span class="error" id="err-phone" hidden></span>
                    </label>
                    <button type="submit">Saada tulemus</button>
                </form>
            </section>
        </main>
        <script src="{{ asset('js/app.js') }}"></script>
    </body>
</html>

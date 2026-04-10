<!-- <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <style>
        @page {
            margin: 40px 50px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12pt;
            color: #e20f0f;
            margin: 0;
            padding: 0;
        }
        header {
            text-align: center;
            margin-bottom: 20px;
        }
        header h1 {
            font-size: 22pt;
            margin: 0;
        }
        header p {
            font-size: 10pt;
            color: #2c2a2a;
        }
        .section {
            min-height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            page-break-after: always;
            padding-top: 40px;
        }
        .section:last-child {
            page-break-after: auto;
        }
        .section h3 {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 25px;
            color: #1c1d1e;
            text-align: center;
        }
        .section img {
            max-width: 75%;
            max-height: 65vh;
            border: 1px solid #ddd;
            padding: 8px;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.12);
            object-fit: contain;
        }
        footer {
            position: fixed;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-size: 9pt;
            color: #999;
        }
        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<header>
    <h1>Fridge Customer Update Images</h1>
     {{-- <p>Request UUID: {{ $uuid }}</p> --}} -->
    <!-- <hr />
</header>

@if(!empty($images))
    @foreach($images as $img)
        @if(file_exists(public_path($img['path'])))
            <div class="section">
                <h3>{{ $img['label'] }}</h3>
                <img src="{{ public_path($img['path']) }}" />
            </div>
        @endif
    @endforeach
@else
    <p style="text-align:center; color:#888; margin-top:50px;">No images found for this request.</p>
@endif

<footer>
    Page {PAGE_NUM} of {PAGE_COUNT}
</footer>

</body>
</html> -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 40px 50px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12pt;
            color: #e51a1a;
        }
        header {
            text-align: center;
            margin-bottom: 20px;
        }
        header h1 {
            font-size: 22pt;
            margin: 0;
        }
        header p {
            font-size: 8pt;
            color: #666;
        }
        .page {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            padding: 40px 0;
            /* Use avoid breaking inside to reduce blank page */
            page-break-inside: avoid;
            min-height: calc(100vh - 120px);
        }
        .page:last-child {
            page-break-after: auto;
        }
        .image-block {
            text-align: center;
            margin-bottom: 20px;
        }
        .image-block h3 {
            font-size: 16pt; 
            font-weight: bold;
            margin-bottom: 15px;
            color: #1c1d1e;
        }
        .image-block img {
            max-width: 450px;
            max-height: 300px;
            border: 1px solid #ddd;
            padding: 5px;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
            display: block;
            margin: 0 auto;
        }
        footer {
            position: fixed;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-size: 9pt;
            color: #999;
        }
        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<header>
    <h1>Chiller Request Images</h1>
    <!-- {{-- <p>Request UUID: {{ $uuid }}</p> --}} -->
    <hr>
</header>


@php
    $chunks = collect($images)->chunk(2);
@endphp

@foreach($chunks as $index => $chunk)
    <div class="page" style="page-break-after: {{ $index + 1 == count($chunks) ? 'auto' : 'always' }}">
        @foreach($chunk as $img)
            @if(file_exists(public_path($img['path'])))
                <div class="image-block">
                    <h3>{{ $img['label'] }}</h3>
                    <img src="{{ public_path($img['path']) }}">
                </div>
            @endif
        @endforeach
    </div>
@endforeach



</body>
</html>
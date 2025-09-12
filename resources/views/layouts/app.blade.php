<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'スクレイピング管理')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 200px;
            background-color: #f8f9fa;
            padding: 1rem;
            border-right: 1px solid #dee2e6;
        }
        .content {
            flex-grow: 1;
            padding: 2rem;
        }
        .nav-link.active {
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>
<body class="bg-light text-dark">

    <div class="sidebar">
        <h5 class="mb-3">メニュー</h5>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="{{ route('emails.index') }}" class="nav-link {{ request()->routeIs('scraping.index') ? 'active' : '' }}">
                    メール反響一覧
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('calls.index') }}" class="nav-link {{ request()->routeIs('scraping.index') ? 'active' : '' }}">
                    電話反響一覧
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('jobapps.index') }}" class="nav-link {{ request()->routeIs('scraping.index') ? 'active' : '' }}">
                    求人反響一覧
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('scraping.index') }}" class="nav-link {{ request()->routeIs('scraping.sources') ? 'active' : '' }}">
                    記事一覧
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('import.guide') }}" class="nav-link {{ request()->routeIs('scraping.sources') ? 'active' : '' }}">
                    取り込み手順
                </a>
            </li>
        </ul>
    </div>

    <div class="content">
        @yield('content')
    </div>

</body>
</html>
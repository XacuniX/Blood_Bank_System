<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blood Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="/bloodbank/index.php">Blood Bank</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="/bloodbank/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/bloodbank/add_donor.php">Add Donor</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/bloodbank/inventory.php">Inventory</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/bloodbank/donor_search.php">Search</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="flex-fill">

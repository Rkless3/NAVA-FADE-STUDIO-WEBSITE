<?php

session_start();

/*
|--------------------------------------------------------------------------
| ADMIN LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once "../config/Database.php";
require_once "../classes/Product.php";

$database = new Database();
$db = $database->connect();

$productModel = new Product($db);


/*
|--------------------------------------------------------------------------
| PRODUCT ID
|--------------------------------------------------------------------------
*/

$id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($id <= 0) {
    header("Location: products.php?message=error");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/

$product = $productModel->getById($id);

if (!$product) {
    header("Location: products.php?message=error");
    exit;
}


/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
|
| Add Stock is only available when current stock is 0.
|
*/

if ((int) $product["stock"] > 0) {
    header("Location: products.php?message=stock_not_empty");
    exit;
}


/*
|--------------------------------------------------------------------------
| ADD STOCK
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $quantity = filter_input(
        INPUT_POST,
        "quantity",
        FILTER_VALIDATE_INT
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE
    |--------------------------------------------------------------------------
    */

    if (
        $quantity === false ||
        $quantity === null ||
        $quantity <= 0
    ) {

        header(
            "Location: add-stock.php?id=" .
            $id .
            "&message=invalid"
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ADD
    |--------------------------------------------------------------------------
    */

    if (
        $productModel->addStock(
            $id,
            $quantity
        )
    ) {

        header(
            "Location: products.php?message=stock_added"
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ERROR
    |--------------------------------------------------------------------------
    */

    header(
        "Location: add-stock.php?id=" .
        $id .
        "&message=error"
    );

    exit;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Add Stock | NAVA Fade Studio
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        :root {

            --navy: #0e1423;
            --gold: #b8862c;
            --light-gold: #d4a33a;
            --white: #ffffff;
            --gray: #555;
            --red: #e57373;

        }


        body {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background:
                linear-gradient(
                    rgba(8, 13, 27, 0.95),
                    rgba(8, 13, 27, 0.98)
                ),
                url("../assets/images/pattern3.png")
                center / cover;

            color: var(--white);

        }


        .container {

            width: 100%;

            max-width: 520px;

        }


        .card {

            background: var(--navy);

            padding: 40px;

            border-radius: 16px;

            border:
                1px solid
                rgba(184, 134, 44, 0.45);

            box-shadow:
                0 20px 50px
                rgba(0, 0, 0, 0.4);

        }


        h1 {

            color: var(--gold);

            margin-bottom: 8px;

        }


        .subtitle {

            color: #999;

            margin-bottom: 28px;

            font-size: 14px;

        }


        .product-box {

            padding: 20px;

            margin-bottom: 25px;

            background: #151c2d;

            border-radius: 10px;

            border:
                1px solid
                rgba(184, 134, 44, 0.25);

        }


        .product-name {

            font-size: 20px;

            font-weight: bold;

            margin-bottom: 8px;

        }


        .stock {

            color: #aaa;

            font-size: 14px;

        }


        .stock strong {

            color: var(--gold);

            font-size: 18px;

        }


        .message {

            padding: 13px 15px;

            margin-bottom: 20px;

            border-radius: 8px;

            background:
                rgba(229, 115, 115, 0.12);

            border:
                1px solid
                rgba(229, 115, 115, 0.45);

            color: var(--red);

        }


        label {

            display: block;

            margin-bottom: 8px;

            font-weight: bold;

        }


        input {

            width: 100%;

            padding: 14px;

            border-radius: 8px;

            border:
                1px solid
                #394256;

            background: #151c2d;

            color: white;

            outline: none;

            font-size: 16px;

        }


        input:focus {

            border-color: var(--gold);

        }


        .hint {

            display: block;

            margin-top: 7px;

            color: #777;

            font-size: 12px;

        }


        .actions {

            display: flex;

            gap: 12px;

            margin-top: 25px;

        }


        .btn {

            flex: 1;

            padding: 14px;

            border-radius: 8px;

            text-align: center;

            text-decoration: none;

            font-weight: bold;

            cursor: pointer;

        }


        .add-btn {

            border: none;

            background: var(--gold);

            color: var(--navy);

        }


        .add-btn:hover {

            background: var(--light-gold);

        }


        .cancel-btn {

            border:
                2px solid
                var(--gold);

            color: var(--gold);

            background: transparent;

        }


        .cancel-btn:hover {

            background: var(--gold);

            color: var(--navy);

        }


        @media (max-width: 550px) {

            .card {
                padding: 25px;
            }

            .actions {
                flex-direction: column;
            }

        }

    </style>

</head>


<body>

<div class="container">

    <div class="card">

        <h1>
            Add Stock
        </h1>

        <p class="subtitle">
            Replenish this product after it reaches zero stock.
        </p>


        <?php if (
            isset($_GET["message"]) &&
            $_GET["message"] === "invalid"
        ): ?>

            <div class="message">
                Please enter a quantity greater than 0.
            </div>

        <?php elseif (
            isset($_GET["message"]) &&
            $_GET["message"] === "error"
        ): ?>

            <div class="message">
                Unable to add stock. Please try again.
            </div>

        <?php endif; ?>


        <div class="product-box">

            <div class="product-name">

                <?= htmlspecialchars(
                    $product["product_name"]
                ) ?>

            </div>

            <div class="stock">

                Current Stock:

                <strong>
                    <?= (int) $product["stock"] ?>
                </strong>

                units

            </div>

        </div>


        <form method="POST">

            <label for="quantity">
                Quantity to Add
            </label>

            <input
                type="number"
                id="quantity"
                name="quantity"
                min="1"
                step="1"
                required
                autofocus
                placeholder="Enter quantity"
            >

            <span class="hint">
                The entered quantity will be added to the current stock.
            </span>


            <div class="actions">

                <button
                    type="submit"
                    class="btn add-btn"
                >
                    + Add Stock
                </button>


                <a
                    href="products.php"
                    class="btn cancel-btn"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>
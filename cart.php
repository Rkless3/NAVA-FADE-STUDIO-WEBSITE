<?php

session_start();


/* =========================================
   LOGIN REQUIREMENT
========================================= */

if (
    !isset($_SESSION["customer_id"])
) {

    header("Location: login.php");
    exit;

}


/* =========================================
   DATABASE
========================================= */

require_once "config/Database.php";
require_once "classes/Product.php";


$database = new Database();
$db = $database->connect();

$productModel = new Product($db);


/* =========================================
   MAXIMUM ORDER QUANTITY
========================================= */

const MAX_ORDER_QUANTITY = 5;


/* =========================================
   AJAX RESPONSE HELPER
========================================= */

function jsonResponse(
    bool $success,
    string $message = "",
    array $data = []
): void {

    header(
        "Content-Type: application/json"
    );


    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $data
        )
    );


    exit;
}


/* =========================================
   AJAX QUANTITY UPDATE
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["ajax"])
    &&
    $_POST["ajax"] === "1"
) {


    $action =
        $_POST["action"]
        ?? "";


    $product_id =
        (int) (
            $_POST["product_id"]
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | ONLY UPDATE QUANTITY THROUGH AJAX
    |--------------------------------------------------------------------------
    */

    if (
        $action !== "update_quantity"
    ) {

        jsonResponse(
            false,
            "Invalid cart action."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCT ID VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $product_id <= 0
    ) {

        jsonResponse(
            false,
            "Invalid product."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | GET PRODUCT
    |--------------------------------------------------------------------------
    */

    $product =
        $productModel->getById(
            $product_id
        );


    if (!$product) {

        unset(
            $_SESSION["cart"][
                $product_id
            ]
        );


        jsonResponse(
            false,
            "This product is no longer available."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ACTIVE STATUS
    |--------------------------------------------------------------------------
    */

    if (
        $product["status"] !== "Active"
    ) {

        unset(
            $_SESSION["cart"][
                $product_id
            ]
        );


        jsonResponse(
            false,
            "This product is no longer available."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | REQUESTED QUANTITY
    |--------------------------------------------------------------------------
    */

    $requestedQuantity =
        filter_input(
            INPUT_POST,
            "quantity",
            FILTER_VALIDATE_INT
        );


    if (
        $requestedQuantity === false
        ||
        $requestedQuantity === null
    ) {

        jsonResponse(
            false,
            "Invalid quantity."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | AVAILABLE STOCK
    |--------------------------------------------------------------------------
    */

    $availableStock =
        (int) $product["stock"];


    /*
    |--------------------------------------------------------------------------
    | MAXIMUM CUSTOMER QUANTITY
    |--------------------------------------------------------------------------
    */

    $maxQuantity =
        min(
            $availableStock,
            MAX_ORDER_QUANTITY
        );


    /*
    |--------------------------------------------------------------------------
    | REMOVE IF ZERO
    |--------------------------------------------------------------------------
    */

    if (
        $requestedQuantity <= 0
    ) {

        unset(
            $_SESSION["cart"][
                $product_id
            ]
        );

        $cartQuantity = 0;


    } else {


        /*
        |--------------------------------------------------------------------------
        | REJECT ABOVE MAXIMUM
        |--------------------------------------------------------------------------
        */

        if (
            $requestedQuantity >
            $maxQuantity
        ) {

            jsonResponse(
                false,
                "Maximum quantity for this product is "
                . $maxQuantity
                . ".",
                [
                    "max_quantity" =>
                        $maxQuantity,

                    "stock" =>
                        $availableStock
                ]
            );

        }


        /*
        |--------------------------------------------------------------------------
        | SAVE QUANTITY
        |--------------------------------------------------------------------------
        */

        $_SESSION["cart"][
            $product_id
        ] =
            $requestedQuantity;


        $cartQuantity =
            $requestedQuantity;

    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCT SUBTOTAL
    |--------------------------------------------------------------------------
    */

    $subtotal =
        $product["price"]
        *
        $cartQuantity;


    /*
    |--------------------------------------------------------------------------
    | CALCULATE COMPLETE CART TOTAL
    |--------------------------------------------------------------------------
    */

    $cartTotal = 0;

    $cartItemCount = 0;


    if (
        !empty(
            $_SESSION["cart"]
        )
    ) {


        foreach (
            $_SESSION["cart"]
            as $cartProductId =>
            $cartProductQuantity
        ) {


            $cartProduct =
                $productModel->getById(
                    (int) $cartProductId
                );


            if (
                !$cartProduct
                ||
                $cartProduct["status"]
                !==
                "Active"
            ) {

                continue;

            }


            $cartProductQuantity =
                (int) $cartProductQuantity;


            $cartTotal +=
                (
                    (float)
                    $cartProduct["price"]
                )
                *
                $cartProductQuantity;


            $cartItemCount +=
                $cartProductQuantity;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    jsonResponse(
        true,
        "Cart updated.",
        [

            "product_id" =>
                $product_id,

            "quantity" =>
                $cartQuantity,

            "stock" =>
                $availableStock,

            "max_quantity" =>
                $maxQuantity,

            "price" =>
                (float) $product["price"],

            "subtotal" =>
                (float) $subtotal,

            "cart_total" =>
                (float) $cartTotal,

            "cart_item_count" =>
                $cartItemCount

        ]
    );

}


/* =========================================
   NORMAL CART ACTIONS
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


    $product_id =
        (int) (
            $_POST["product_id"]
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | INCREASE
    |--------------------------------------------------------------------------
    */

    if (
        isset($_POST["increase"])
    ) {


        if (
            isset(
                $_SESSION["cart"][
                    $product_id
                ]
            )
        ) {


            $product =
                $productModel->getById(
                    $product_id
                );


            if ($product) {


                $currentQuantity =
                    (int)
                    $_SESSION["cart"][
                        $product_id
                    ];


                $availableStock =
                    (int)
                    $product["stock"];


                $maxQuantity =
                    min(
                        $availableStock,
                        MAX_ORDER_QUANTITY
                    );


                if (
                    $currentQuantity <
                    $maxQuantity
                ) {

                    $_SESSION["cart"][
                        $product_id
                    ]++;

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | DECREASE
    |--------------------------------------------------------------------------
    */

    if (
        isset($_POST["decrease"])
    ) {


        if (
            isset(
                $_SESSION["cart"][
                    $product_id
                ]
            )
        ) {


            $_SESSION["cart"][
                $product_id
            ]--;


            if (
                $_SESSION["cart"][
                    $product_id
                ] <= 0
            ) {

                unset(
                    $_SESSION["cart"][
                        $product_id
                    ]
                );

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE
    |--------------------------------------------------------------------------
    */

    if (
        isset($_POST["remove"])
    ) {

        unset(
            $_SESSION["cart"][
                $product_id
            ]
        );

    }


    /*
    |--------------------------------------------------------------------------
    | REFRESH
    |--------------------------------------------------------------------------
    */

    header(
        "Location: cart.php"
    );

    exit;

}


/* =========================================
   GET CART
========================================= */

$cart =
    $_SESSION["cart"]
    ?? [];


$total = 0;

$totalItems = 0;

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
        Cart | NAVA Fade Studio
    </title>


    <!-- MAIN WEBSITE CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <!-- CART CSS -->

    <link
        rel="stylesheet"
        href="assets/css/cart.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | QUANTITY CONTROL
        |--------------------------------------------------------------------------
        */

        .cart-quantity {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 12px;

        }


        .cart-quantity button {

            width: 36px;

            height: 36px;

            border-radius: 8px;

            border: 1px solid #596274;

            background: #111827;

            color: #ffffff;

            font-size: 20px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.2s ease;

        }


        .cart-quantity button:hover:not(:disabled) {

            border-color: #b8862c;

            color: #b8862c;

        }


        .cart-quantity button:disabled {

            opacity: 0.35;

            cursor: not-allowed;

        }


        .cart-quantity .quantity-value {

            min-width: 28px;

            text-align: center;

            font-size: 16px;

            font-weight: bold;

        }


        /*
        |--------------------------------------------------------------------------
        | STOCK INFO
        |--------------------------------------------------------------------------
        */

        .cart-stock-info {

            margin-top: 7px;

            color: #7f8a9d;

            font-size: 12px;

        }


        .cart-stock-info strong {

            color: #b8862c;

        }


        /*
        |--------------------------------------------------------------------------
        | CART MESSAGE
        |--------------------------------------------------------------------------
        */

        .cart-message {

            display: none;

            margin-bottom: 20px;

            padding: 12px 16px;

            border-radius: 8px;

            background:
                rgba(184, 134, 44, 0.12);

            border:
                1px solid
                rgba(184, 134, 44, 0.35);

            color: #d4a33a;

            font-size: 14px;

        }


        .cart-message.show {

            display: block;

        }


        /*
        |--------------------------------------------------------------------------
        | LOADING
        |--------------------------------------------------------------------------
        */

        .quantity-loading {

            pointer-events: none;

            opacity: 0.6;

        }

    </style>

</head>


<body>


<main class="cart-page">


    <!-- =====================================
         CART HEADER
    ====================================== -->

    <div class="cart-page-header">


        <span>
            NAVA FADE STUDIO
        </span>


        <h1>
            Your Cart
        </h1>


    </div>


    <div class="cart-container">


        <!-- CART MESSAGE -->

        <div
            class="cart-message"
            id="cartMessage"
        ></div>


        <?php if (
            isset($_GET["message"])
            &&
            $_GET["message"] ===
            "max_quantity"
        ): ?>


            <div
                class="cart-message show"
                style="display:block;"
            >

                You have reached the maximum
                quantity allowed for this product.

            </div>


        <?php endif; ?>


        <?php if (
            empty($cart)
        ): ?>


            <!-- =================================
                 EMPTY CART
            ================================== -->

            <div class="cart-empty">


                <h2>
                    Your Cart is Empty
                </h2>


                <p>
                    You haven't added any products
                    to your cart yet.
                </p>


                <a
                    href="shop.php"
                    class="cart-continue"
                >
                    Continue Shopping
                </a>


            </div>


        <?php else: ?>


            <!-- =================================
                 CART PRODUCTS
            ================================== -->


            <?php foreach (
                $cart as $product_id =>
                $quantity
            ): ?>


                <?php

                $product =
                    $productModel->getById(
                        (int) $product_id
                    );


                /*
                |--------------------------------------------------------------------------
                | SKIP MISSING PRODUCT
                |--------------------------------------------------------------------------
                */

                if (!$product) {

                    continue;

                }


                /*
                |--------------------------------------------------------------------------
                | CURRENT QUANTITY
                |--------------------------------------------------------------------------
                */

                $quantity =
                    (int) $quantity;


                /*
                |--------------------------------------------------------------------------
                | STOCK
                |--------------------------------------------------------------------------
                */

                $availableStock =
                    (int) $product["stock"];


                /*
                |--------------------------------------------------------------------------
                | MAXIMUM
                |--------------------------------------------------------------------------
                */

                $maxQuantity =
                    min(
                        $availableStock,
                        MAX_ORDER_QUANTITY
                    );


                /*
                |--------------------------------------------------------------------------
                | PROTECT EXISTING SESSION CART
                |--------------------------------------------------------------------------
                |
                | If stock was reduced after the item
                | was placed in the cart, don't display
                | an impossible quantity.
                |
                */

                if (
                    $availableStock <= 0
                ) {

                    unset(
                        $_SESSION["cart"][
                            $product_id
                        ]
                    );

                    continue;

                }


                if (
                    $quantity >
                    $maxQuantity
                ) {

                    $quantity =
                        $maxQuantity;


                    $_SESSION["cart"][
                        $product_id
                    ] =
                        $maxQuantity;

                }


                /*
                |--------------------------------------------------------------------------
                | SUBTOTAL
                |--------------------------------------------------------------------------
                */

                $subtotal =
                    (
                        (float)
                        $product["price"]
                    )
                    *
                    $quantity;


                $total +=
                    $subtotal;


                $totalItems +=
                    $quantity;

                ?>


                <div
                    class="cart-item"
                    data-product-id="<?= (int) $product_id ?>"
                >


                    <!-- =================================
                         PRODUCT INFORMATION
                    ================================== -->


                    <div
                        class="cart-item-info"
                    >


                        <h3>
                            <?= htmlspecialchars(
                                $product["product_name"]
                            ) ?>
                        </h3>


                        <p>
                            Price:
                            ₱<?= number_format(
                                $product["price"],
                                2
                            ) ?>
                        </p>


                        <p
                            class="cart-stock-info"
                        >

                            Available:

                            <strong>
                                <?= $availableStock ?>
                            </strong>

                            &nbsp;|&nbsp;

                            Max per order:

                            <strong>
                                <?= $maxQuantity ?>
                            </strong>

                        </p>


                    </div>


                    <!-- =================================
                         QUANTITY
                    ================================== -->


                    <div
                        class="cart-quantity"
                        data-product-id="<?= (int) $product_id ?>"
                        data-stock="<?= $availableStock ?>"
                        data-max="<?= $maxQuantity ?>"
                    >


                        <!-- DECREASE -->

                        <button
                            type="button"
                            class="quantity-decrease"
                            data-product-id="<?= (int) $product_id ?>"
                            aria-label="Decrease quantity"
                            <?= $quantity <= 1
                                ? "disabled"
                                : ""
                            ?>
                        >
                            −
                        </button>


                        <!-- CURRENT QUANTITY -->

                        <span
                            class="quantity-value"
                            id="quantity-<?= (int) $product_id ?>"
                        >
                            <?= $quantity ?>
                        </span>


                        <!-- INCREASE -->

                        <button
                            type="button"
                            class="quantity-increase"
                            data-product-id="<?= (int) $product_id ?>"
                            aria-label="Increase quantity"
                            <?= $quantity >= $maxQuantity
                                ? "disabled"
                                : ""
                            ?>
                        >
                            +
                        </button>


                    </div>


                    <!-- =================================
                         SUBTOTAL
                    ================================== -->


                    <div
                        class="cart-subtotal"
                        id="subtotal-<?= (int) $product_id ?>"
                    >

                        ₱<?= number_format(
                            $subtotal,
                            2
                        ) ?>

                    </div>


                    <!-- =================================
                         REMOVE
                    ================================== -->


                    <form
                        method="POST"
                        action="cart.php"
                        class="cart-remove"
                    >


                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int) $product_id ?>"
                        >


                        <button
                            type="submit"
                            name="remove"
                        >
                            Remove
                        </button>


                    </form>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>


    <?php if (
        !empty($cart)
    ): ?>


        <!-- =====================================
             CART SUMMARY
        ====================================== -->


        <div class="cart-summary">


            <div
                class="cart-summary-box"
            >


                <h2>
                    Cart Summary
                </h2>


                <div class="cart-total">


                    <span>
                        Total
                    </span>


                    <span id="cartTotal">

                        ₱<?= number_format(
                            $total,
                            2
                        ) ?>

                    </span>


                </div>


                <!-- CONTINUE SHOPPING -->


                <a
                    href="shop.php"
                    class="cart-continue"
                >
                    Continue Shopping
                </a>


                <!-- CHECKOUT -->


                <a
                    href="checkout.php"
                    class="cart-continue"
                    id="checkoutButton"
                >
                    Checkout
                </a>


            </div>


        </div>


    <?php endif; ?>


</main>


<script>


/* =========================================
   CART AJAX UPDATE
========================================= */

const cartMessage =
    document.getElementById(
        "cartMessage"
    );


/* =========================================
   SHOW MESSAGE
========================================= */

function showCartMessage(
    message
) {

    if (!cartMessage) {
        return;
    }


    cartMessage.textContent =
        message;


    cartMessage.classList.add(
        "show"
    );


    setTimeout(
        function() {

            cartMessage.classList.remove(
                "show"
            );

        },
        2500
    );

}


/* =========================================
   FORMAT PESO
========================================= */

function formatPeso(
    amount
) {

    return "₱"
        +
        Number(amount).toLocaleString(
            "en-PH",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

}


/* =========================================
   UPDATE CART QUANTITY
========================================= */

async function updateQuantity(
    productId,
    newQuantity,
    control
) {


    if (!control) {
        return;
    }


    const maxQuantity =
        parseInt(
            control.dataset.max,
            10
        )
        ||
        0;


    /*
    |--------------------------------------------------------------------------
    | CLIENT-SIDE PROTECTION
    |--------------------------------------------------------------------------
    */

    if (
        newQuantity < 1
    ) {

        return;

    }


    if (
        newQuantity >
        maxQuantity
    ) {

        showCartMessage(
            "Maximum quantity is "
            +
            maxQuantity
            +
            "."
        );

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | DISABLE CONTROLS WHILE UPDATING
    |--------------------------------------------------------------------------
    */

    control.classList.add(
        "quantity-loading"
    );


    const buttons =
        control.querySelectorAll(
            "button"
        );


    buttons.forEach(
        function(button) {

            button.disabled =
                true;

        }
    );


    /*
    |--------------------------------------------------------------------------
    | REQUEST
    |--------------------------------------------------------------------------
    */

    const formData =
        new FormData();


    formData.append(
        "ajax",
        "1"
    );


    formData.append(
        "action",
        "update_quantity"
    );


    formData.append(
        "product_id",
        productId
    );


    formData.append(
        "quantity",
        newQuantity
    );


    try {


        const response =
            await fetch(
                "cart.php",
                {
                    method: "POST",
                    body: formData
                }
            );


        const data =
            await response.json();


        /*
        |--------------------------------------------------------------------------
        | FAILED
        |--------------------------------------------------------------------------
        */

        if (!data.success) {

            showCartMessage(
                data.message
                ||
                "Unable to update cart."
            );


            /*
            |--------------------------------------------------------------------------
            | RESTORE CONTROLS
            |--------------------------------------------------------------------------
            */

            updateButtonStates(
                control
            );


            return;

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE QUANTITY
        |--------------------------------------------------------------------------
        */

        const quantityElement =
            document.getElementById(
                "quantity-"
                +
                productId
            );


        if (quantityElement) {

            quantityElement.textContent =
                data.quantity;

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE SUBTOTAL
        |--------------------------------------------------------------------------
        */

        const subtotalElement =
            document.getElementById(
                "subtotal-"
                +
                productId
            );


        if (subtotalElement) {

            subtotalElement.textContent =
                formatPeso(
                    data.subtotal
                );

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE CART TOTAL
        |--------------------------------------------------------------------------
        */

        const cartTotal =
            document.getElementById(
                "cartTotal"
            );


        if (cartTotal) {

            cartTotal.textContent =
                formatPeso(
                    data.cart_total
                );

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE MAXIMUM
        |--------------------------------------------------------------------------
        */

        control.dataset.stock =
            data.stock;


        control.dataset.max =
            data.max_quantity;


        /*
        |--------------------------------------------------------------------------
        | UPDATE BUTTONS
        |--------------------------------------------------------------------------
        */

        updateButtonStates(
            control
        );


    } catch (error) {


        console.error(
            error
        );


        showCartMessage(
            "Connection error. Please try again."
        );


        updateButtonStates(
            control
        );


    } finally {


        control.classList.remove(
            "quantity-loading"
        );


    }

}


/* =========================================
   BUTTON STATE
========================================= */

function updateButtonStates(
    control
) {


    const quantityElement =
        control.querySelector(
            ".quantity-value"
        );


    if (!quantityElement) {
        return;
    }


    const quantity =
        parseInt(
            quantityElement.textContent,
            10
        )
        ||
        1;


    const maxQuantity =
        parseInt(
            control.dataset.max,
            10
        )
        ||
        1;


    const decreaseButton =
        control.querySelector(
            ".quantity-decrease"
        );


    const increaseButton =
        control.querySelector(
            ".quantity-increase"
        );


    /*
    |--------------------------------------------------------------------------
    | DECREASE
    |--------------------------------------------------------------------------
    */

    if (decreaseButton) {

        decreaseButton.disabled =
            quantity <= 1;

    }


    /*
    |--------------------------------------------------------------------------
    | INCREASE
    |--------------------------------------------------------------------------
    */

    if (increaseButton) {

        increaseButton.disabled =
            quantity >= maxQuantity;

    }

}


/* =========================================
   INITIALIZE CONTROLS
========================================= */

document
    .querySelectorAll(
        ".cart-quantity"
    )
    .forEach(
        function(control) {


            updateButtonStates(
                control
            );


        }
    );


/* =========================================
   PLUS BUTTON
========================================= */

document
    .querySelectorAll(
        ".quantity-increase"
    )
    .forEach(
        function(button) {


            button.addEventListener(
                "click",
                function() {


                    const productId =
                        parseInt(
                            this.dataset.productId,
                            10
                        );


                    const control =
                        this.closest(
                            ".cart-quantity"
                        );


                    const quantityElement =
                        control.querySelector(
                            ".quantity-value"
                        );


                    const currentQuantity =
                        parseInt(
                            quantityElement.textContent,
                            10
                        )
                        ||
                        1;


                    const maxQuantity =
                        parseInt(
                            control.dataset.max,
                            10
                        )
                        ||
                        1;


                    /*
                    |--------------------------------------------------------------------------
                    | MAXIMUM CHECK
                    |--------------------------------------------------------------------------
                    */

                    if (
                        currentQuantity >=
                        maxQuantity
                    ) {

                        showCartMessage(
                            "Maximum quantity is "
                            +
                            maxQuantity
                            +
                            "."
                        );

                        return;

                    }


                    updateQuantity(
                        productId,
                        currentQuantity + 1,
                        control
                    );


                }
            );


        }
    );


/* =========================================
   MINUS BUTTON
========================================= */

document
    .querySelectorAll(
        ".quantity-decrease"
    )
    .forEach(
        function(button) {


            button.addEventListener(
                "click",
                function() {


                    const productId =
                        parseInt(
                            this.dataset.productId,
                            10
                        );


                    const control =
                        this.closest(
                            ".cart-quantity"
                        );


                    const quantityElement =
                        control.querySelector(
                            ".quantity-value"
                        );


                    const currentQuantity =
                        parseInt(
                            quantityElement.textContent,
                            10
                        )
                        ||
                        1;


                    /*
                    |--------------------------------------------------------------------------
                    | MINIMUM CHECK
                    |--------------------------------------------------------------------------
                    */

                    if (
                        currentQuantity <= 1
                    ) {

                        return;

                    }


                    updateQuantity(
                        productId,
                        currentQuantity - 1,
                        control
                    );


                }
            );


        }
    );

</script>


</body>

</html>
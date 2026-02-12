<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    echo json_encode(["admin" => false]);
} else {
    echo json_encode(["admin" => true]);
}

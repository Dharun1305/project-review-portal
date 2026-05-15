<?php
session_start();
include("../configuration/db.php");
if(!$conn){
    die("Database not connected");
} else {
    echo "DB Connected <br>";
}

if(isset($_POST['email']) && isset($_POST['password']) && isset($_POST['role'])){

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Check only email and password first
    $query = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) == 1){

        $row = mysqli_fetch_assoc($result);

        // Now check role separately
        if($row['role'] == $role){

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $row['role'];

            if($role == "admin"){
                header("Location: ../../admin-dashboard.php");
                exit();
            }
            elseif($role == "faculty"){
                header("Location: ../../faculty-dashboard.php");
                exit();
            }
            elseif($role == "student"){
                header("Location: ../../student-dashboard.php");
                exit();
            }

        } else {
            echo "Role does not match!";
        }

    } else {
        echo "Invalid Email or Password!";
    }

} else {
    echo "Please fill all fields!";
}
?>
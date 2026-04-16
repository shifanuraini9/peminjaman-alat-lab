<?php
echo "user (1234): " . password_hash("1234", PASSWORD_DEFAULT) . "<br><br>";
echo "petugas (123456): " . password_hash("123456", PASSWORD_DEFAULT) . "<br><br>";
echo "admin (098765): " . password_hash("098765", PASSWORD_DEFAULT);

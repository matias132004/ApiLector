<?php
    include('datosConexionLector.php');
    
    class ConexionLector{
            function Conectar(){
                try{
                    $conexion = new PDO("pgsql:host=".SERVER.";dbname=".DBNAME, USER, PASS);
                    return $conexion;
                } 
                catch (Exception $e){
                    die("Error: ".$e->getMessage());
                }
            }
    }
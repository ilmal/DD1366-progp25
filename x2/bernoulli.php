<?php
    // RUN FILE: 
    //          php bernoulli.php

    // Function that creates bernoulli number like the pseudocode
    function B($n): float {
        $B = array();   // initialize emtpy array
        $B[0] = 1;      // set first elem to 1

        // For-loop nr 1
        for ($m = 1; $m <= $n; $m++) {
            $B[$m] = 0;
            // For-loop nr 2
            for ($k = 0; $k <= $m-1; $k++) {
                $B[$m] = $B[$m] - binom($m+1, $k) * $B[$k];
            }
            $B[$m] = $B[$m]/($m+1);
        }        
        return $B[$n];
    }

    function binom($n, $k) {
        $r = 1;
        for ($i = 1; $i <= $k; $i++) {
            $r = $r * ($n - $i + 1)/$i;
        }
        return $r;
    }
    // Test binom: should output 10
    // echo binom(5, 2);
    // echo "\n";

    // $res = B(4); // output = -0.033333... = -1/30
    // echo "B(1) = ";
    // echo $res;
    // echo "\n";

    // Loop to print the first 10 Bernoulli numbers
    for ($n = 0; $n < 10; $n++) {
        $res = B($n);
        echo "B($n) = $res\n";
    }
?>
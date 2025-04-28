// COMPILE & RUN FILE: 
//          rustc bernoulli.rs
//          ./bernoulli

// Function for binomial
fn binom(n: u16, k: u16) -> f64{
    let mut r = 1.0;
    
    for i in 1..= k {
        r = r * (n-i+1) as f64/i as f64;        //  r · (n − i + 1)/i
    }

    return r;
}

// mut -> gör att variabeln är muteable och kan ändras
fn B(n: u16) -> f64 {
    let mut b: Vec<f64> = vec![0.0; (n + 1) as usize]; // Create a vector to store B values
    
    b[0] = 1.0; // B0 = 1
    
    for m in 1..=n {
        b[m as usize] = 0.0;
        for k in 0..=m-1 {
            b[m as usize] -= binom(m + 1, k) * b[k as usize]; // B[m] = B[m] - binom(m+1, k) * B[k]
        }
        b[m as usize] /= (m + 1) as f64; // B[m] = B[m] / (m+1)
    }
    
    return b[n as usize] // Return B[n]
}

fn main() {
    // let test = binom(5, 2);
    // println!("{}", test);

    // let bernoulli = B(1); 
    // println!("B(4) = {}", bernoulli);

    // print first 10 bernoulli numbers
    for n in 0..10 {
        let nr = B(n);
        println!("B({}) = {}", n, nr);
    }
}
"RUN FILE: 
         gst bernoulli.st"

" bernoulli-func "
b := [:n |
    | b |
    b := OrderedCollection with: 1.  "dynamnic array -> B[0] = 1"

    " from 1-n, each m should do... "
    1 to: n do: [:m |
        b add: 0.  "add B[m+1]=0 to dynamic array"

        " from 0-(m-1), each k should do... "
        0 to: m - 1 do: [:k |
            b at: m + 1 put: (b at: m + 1) - ((binom value: m + 1 value: k) * (b at: k + 1)). "calculate B[m]"
        ].
        b at: m + 1 put: (b at: m + 1) / (m + 1). "divide with (m+1) and store at B[m]"
    ].
    b at: n + 1 "return"
].

" binom func, return binomial of two numbers 
    |r| means temporary variable 
"
binom := [:n :k |
    | r | 
    r := 1.
    1 to: k do: [:i | r := r * (n-i+1)/i].
    r
].

" Print function for first 10 Bernoulli numbers "
printB := [
    0 to: 9 do: [:i |
        Transcript 
            show: 'B(';
            show: i asString;
            show: ') = ';
            show: (b value: i) asString; 
            cr.
    ].
].

" Main execution "
printB value.



" result := binom value:5 value:2.
Transcript show: 'binom(5,2) = ', result asString; cr. "

" result := b value:4.
Transcript show: result asString; cr. "
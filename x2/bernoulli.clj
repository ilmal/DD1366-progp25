;; RUN FILE:
;;           clojure bernoulli.clj

; Func for binom which takes parameters n and k
(defn binom [n k]
  (let [r (atom 1.0)] ; define r = 1 as atom (muteable)
    ; loop in range 1 to k inclusive
    (doseq [i (range 1 (inc k))]
      ; swap changes atom-value with func value
      (swap! r (fn [current] (* current (/ (+ (- n i) 1) i)))))  ; current = r, divide () with i
    @r) ; @r dereferences variable r and returns r
)

; func that takes in n and returns the bernoulli number
(defn B [n]
  (let [B (atom (vec (repeat (inc n) 0.0)))] ; muteable vector B
    (swap! B assoc 0 1.0) ; set first element to 1
    ; loop m from 1 to n
    (doseq [m (range 1 (inc n))]
      (swap! B assoc m 0.0) ; B[m] = 0
      ; loop k from 0 to m-1 (not inc)
      (doseq [k (range 0 m)]
        (swap! B (fn [x] (assoc x m (- (get x m) (* (binom (inc m) k) (get x k)))))) ; calculate
        )
      (swap! B update m (fn [x] (/ (double x) (+ 1 m)))) ; divide with m+1
      )
    (get @B n)) ; retrieve output
  )

;; (println (binom 5 2))
;; (println (B 4)) ; should return -0.03333... = (-1/30)

(doseq [n (range 10)]
  (println (str "B(" n ") = " (B n))))
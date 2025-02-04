removeEveryNth :: Int -> [Int] -> [Int]
removeEveryNth n xs = reverse (helper xs 1 [] n)
  where
    helper :: [Int] -> Int -> [Int] -> Int -> [Int]
    helper [] _ acc _ = acc
    helper (x:xs) count acc n
      | count == n  = helper xs 1 acc n
      | otherwise   = helper xs (count + 1) (x:acc) n

-- Testad och klar!
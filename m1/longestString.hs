longestString :: [String] -> String

compareLength :: String -> String -> String
compareLength a b = if length a >= length b then a else b

longestString v = foldl compareLength "" v


-- Testad och klar!
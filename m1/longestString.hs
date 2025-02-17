compareLength :: String -> String -> String
-- jämför längden på två strängar och returnerar den längsta. 
-- Ifall element 1 är längre än element 2 returneras element 1, annars element 2.
compareLength a b = if length a >= length b then a else b

longestString :: [String] -> String
-- använder foldl som går igenom listan från vänster och applicerar compareLength mellan strängarna.
longestString v = foldl compareLength "" v


-- Testad och klar!
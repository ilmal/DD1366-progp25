import Data.List(sort, intercalate)

-- Run: 
--      ghc busnumbers.hs
--      ./busnumbers

-- Function that converts the input string into a list of integers instead
-- words: splits string into list of words
-- read: casts a string to another type (in this case integer)
-- map: casts functions read and words on every char in input string
convertToArraylist :: String -> [Int]
convertToArraylist listOfBuslines = map read (words listOfBuslines)

-- Function that groups every consecutive number into lists
--      EXAMPLE: input = [1, 2, 3, 5, 4], output = [[1, 2, 3], [4, 5]]
-- Base case: return empty list for empty list input
-- Recursive case: sort input list in ascending order, call local func 'go' with empty accumulator
groupConsecutive :: [Int] -> [[Int]]
groupConsecutive [] = []
groupConsecutive xs = go (sort xs) []
    where
        go :: [Int] -> [Int] -> [[Int]] 
        go [] acc = [reverse acc] -- if no more nums left in xs, reverse acc
        go [x] acc = [reverse (x:acc)] -- if one element remains, add to acc and reverse 
        go (x:y:ys) acc -- if there are two or more elements left
            | y == x+1 = go (y:ys) (x:acc) -- if x and y are consecutive, 
            | otherwise = reverse (x:acc) : go (y:ys) [] -- if not consecutive, add x and reverse, start new group with y

-- function that takes the grouped list of ints and creates a string with correct format
createString :: [[Int]] -> String
createString sortedBuslines = unwords (map go sortedBuslines)
    where
        go :: [Int] -> String
        go innerlist
            | length innerlist > 2 = show (head innerlist) ++ "-" ++ show (last innerlist) 
            | otherwise = unwords (map show innerlist)

-- mainfunc to read from terminal
main :: IO()
main = do
    -- amount of buslines
    buslines <- getLine

    -- list of buslines
    buslist <- getLine
    let getList = groupConsecutive (convertToArraylist buslist)
    let formattedList = createString getList

    putStrLn formattedList
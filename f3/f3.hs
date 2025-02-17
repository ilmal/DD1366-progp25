module Main where

import UI.HSCurses.Curses
import Control.Monad (when, void)
import Control.Concurrent (threadDelay)
import Foreign.C.Types (CInt)

--  --- Datatyper ---
-- derive från Eq för att kunna jämföra riktningar med == osv 
data Direction = U | D | L | R deriving (Eq)
-- intuitivt...
data Player = Player { pos :: (Int, Int), dir :: Direction }
-- som ovan :))
data GameState = GameState
  { p1 :: Player
  , p2 :: Player
  , walls :: [(Int, Int)]
  , boardWidth  :: Int
  , boardHeight :: Int
  , gameOver    :: Bool
  }

-- startvärden
initialState :: GameState
initialState = GameState
  { p1 = Player (5,5) R
  , p2 = Player (24,14) L
  , walls = []
  , boardWidth  = 30
  , boardHeight = 20
  , gameOver    = False
  }

-- Hantera indata
handleInput :: Int -> GameState -> GameState
handleInput ch state = state { p1 = newP1, p2 = newP2 }
  where
    newP1 = case ch of
              key_UP    -> (p1 state){ dir = U }
              key_DOWN  -> (p1 state){ dir = D }
              key_LEFT  -> (p1 state){ dir = L }
              key_RIGHT -> (p1 state){ dir = R }
              _         -> p1 state
    newP2 = case ch of
              119 -> (p2 state){ dir = U }  -- w
              115 -> (p2 state){ dir = D }  -- s
              97  -> (p2 state){ dir = L }  -- a
              100 -> (p2 state){ dir = R }  -- d
              _   -> p2 state

delta :: Direction -> (Int, Int)
delta U = (0, -1)
delta D = (0, 1)
delta L = (-1, 0)
delta R = (1, 0)

add :: (Int, Int) -> (Int, Int) -> (Int, Int)
add (x,y) (dx,dy) = (x+dx, y+dy)

outOfBounds :: (Int,Int) -> Int -> Int -> Bool
outOfBounds (x,y) w h = x <= 0 || x >= (w-1) || y <= 0 || y >= (h-1)

collision :: Player -> GameState -> Bool
collision player state =
  outOfBounds (pos player) (boardWidth state) (boardHeight state)
  || (pos player) `elem` (walls state)

movePlayers :: GameState -> GameState
movePlayers state
  | collision newP1 state' || collision newP2 state' = state { gameOver = True }
  | otherwise = state { p1 = newP1, p2 = newP2, walls = newWalls }
  where
    newWalls = walls state ++ [ pos (p1 state), pos (p2 state) ]
    newP1 = (p1 state){ pos = add (pos (p1 state)) (delta (dir (p1 state))) }
    newP2 = (p2 state){ pos = add (pos (p2 state)) (delta (dir (p2 state))) }
    state' = state { walls = newWalls }

-- Hjälpfunktion: konvertera Char till ChType
charToCh :: Char -> ChType
charToCh c = fromIntegral (fromEnum c)

-- Rita spelplanen på ett givet fönster
render :: GameState -> Window -> IO ()
render state scr = do
  wclear scr
  drawBorder state scr
  mapM_ (drawWall scr) (walls state)
  drawPlayer scr (p1 state)
  drawPlayer scr (p2 state)
  refresh

drawBorder :: GameState -> Window -> IO ()
drawBorder state scr = do
  let w = boardWidth state
      h = boardHeight state
  mapM_ (\x -> do
           wMove scr 0 x
           void $ waddch scr (charToCh '#')
           wMove scr (h-1) x
           void $ waddch scr (charToCh '#')
        ) [0 .. w-1]
  mapM_ (\y -> do
           wMove scr y 0
           void $ waddch scr (charToCh '#')
           wMove scr y (w-1)
           void $ waddch scr (charToCh '#')
        ) [0 .. h-1]

drawWall :: Window -> (Int,Int) -> IO ()
drawWall scr (x,y) = do
  wMove scr y x
  void $ waddch scr (charToCh '#')

dirToChar :: Direction -> Char
dirToChar U = '^'
dirToChar D = 'v'
dirToChar L = '<'
dirToChar R = '>'

drawPlayer :: Window -> Player -> IO ()
drawPlayer scr (Player (x,y) d) = do
  wMove scr y x
  void $ waddch scr (charToCh (dirToChar d))

-- Spelloopen
gameLoop :: GameState -> Window -> IO ()
gameLoop state scr = do
  if gameOver state
    then do
      mvWAddStr scr (boardHeight state) 0 "Game Over! Tryck på en tangent för att avsluta."
      refresh
      getCh >> return ()
    else do
      ch <- getch
      let intCh = fromIntegral ch :: Int
      let state1 = if intCh == (-1) then state else handleInput intCh state
      let state2 = movePlayers state1
      render state2 scr
      threadDelay 100000  -- 100 ms
      gameLoop state2 scr

main :: IO ()
main = do
  scr <- initScr
  cBreak True
  echo False
  cursSet CursorInvisible
  keypad scr True
  timeout 100
  render initialState scr
  gameLoop initialState scr
  endWin

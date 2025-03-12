data Vec3 = Vec3 Double Double Double deriving (Eq, Show)

-- skalär
dot :: Vec3 -> Vec3 -> Double
dot (Vec3 ax ay az) (Vec3 bx by bz) = ax * bx + ay * by + az * bz

-- mul en vektor med en skalär
smul :: Double -> Vec3 -> Vec3
smul t (Vec3 x y z) = Vec3 (t * x) (t * y) (t * z)

-- vektorsub
sub :: Vec3 -> Vec3 -> Vec3
sub (Vec3 ax ay az) (Vec3 bx by bz) = Vec3 (ax - bx) (ay - by) (az - bz)

-- Neg av en vektor
neg :: Vec3 -> Vec3
neg (Vec3 x y z) = Vec3 (-x) (-y) (-z)

--r = d − 2(d · n')n', där vi först säkerställer att n är rättvänt.
reflect :: Vec3 -> Vec3 -> Vec3
reflect d n = d `sub` smul (2 * dot d n') n'
  where
    n' = if dot d n < 0 then n else neg n

-- ta bort alla ljusstrålar där z-komponent är negativ.
removeNegativeZ :: [Vec3] -> [Vec3]
removeNegativeZ rays = filter (\(Vec3 _ _ z) -> z >= 0) rays

-- sätt ihop reflektion och filtrering med punktnotation.
-- galen curried funktion, först tar den en normalvektor och returnerar en funktion som tar en lista av vektorer.
reflectAndRemoveNegativeZ :: Vec3 -> [Vec3] -> [Vec3]
reflectAndRemoveNegativeZ normal = removeNegativeZ . map (\ray -> reflect ray normal)

{-

Testfall 1

Vec3 0.5 0.5 0.0
Vec3 0.5 0.5 0.0


Vec3 0.2 0.7 0.0
Vec3 0.2 0.7 0.0

Vec3 (-0.5000000000000002) (-3.5) (-0.5000000000000002)
Vec3 (-0.5000000000000002) (-3.5) (-0.5000000000000002)

testfall 2

[Vec3 1.0 2.0 3.0,Vec3 (-0.2) 0.3 0.4]
[Vec3 1.0 2.0 3.0,Vec3 (-0.2) 0.3 0.4]

[Vec3 0.2 0.3 0.3,Vec3 (-0.2) 0.3 0.4]
[Vec3 0.2 0.3 0.3,Vec3 (-0.2) 0.3 0.4]

Testfall 3

[]
[]

[Vec3 1.0 (-2.0) 3.0,Vec3 (-0.2) (-0.3) 0.4]
[Vec3 1.0 (-2.0) 3.0,Vec3 (-0.2) (-0.3) 0.4]

[Vec3 1.0000000000000002 0.3 0.8000000000000002]
[Vec3 1.0000000000000002 0.3 0.8000000000000002]

-}

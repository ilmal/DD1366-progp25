data Vec3 = Vec3 Double Double Double deriving (Eq, Show)

-- Skalärprodukt
dot :: Vec3 -> Vec3 -> Double
dot (Vec3 ax ay az) (Vec3 bx by bz) = ax * bx + ay * by + az * bz

-- Multiplicera en vektor med en skalär
smul :: Double -> Vec3 -> Vec3
smul t (Vec3 x y z) = Vec3 (t * x) (t * y) (t * z)

-- Vektorsubtraktion
sub :: Vec3 -> Vec3 -> Vec3
sub (Vec3 ax ay az) (Vec3 bx by bz) = Vec3 (ax - bx) (ay - by) (az - bz)

-- Negation av en vektor
neg :: Vec3 -> Vec3
neg (Vec3 x y z) = Vec3 (-x) (-y) (-z)

--r = d − 2(d · n')n', där vi först säkerställer att n är "rättvänd".
reflect :: Vec3 -> Vec3 -> Vec3
reflect d n =
  let n' = if dot d n < 0 then n else neg n
  in d `sub` smul (2 * dot d n') n'

-- Filtrerar bort alla ljusstrålar vars z-komponent är negativ.
removeNegativeZ :: [Vec3] -> [Vec3]
removeNegativeZ rays = filter (\(Vec3 _ _ z) -> z >= 0) rays

-- Fogar ihop reflektion och filtrering med punktnotation.
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

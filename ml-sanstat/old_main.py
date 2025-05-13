import pylab as pb
import numpy as np
from math import pi
from scipy . stats import multivariate_normal
from scipy . spatial . distance import cdist

mu = [0, 0] # vantevarde i vektorformat (medelvarde)

# kovariansmatris -> kovarians mellan alla givna par i given random vektor
Cov = np.array([
    [4, 1],  # X->X & X-> Y
    [1, 2]   # Y->X & Y-> Y
])

alpha = 0.2
# Scale correctly to maintain positive semidefiniteness
covAlpha = Cov / alpha
n = 300  # Antal prickar att plotta

f = np . random . multivariate_normal ( mu , covAlpha , n )

# create contour plot of multivariate normal distribution
w0_min, w0_max = f[:, 0].min(), f[:, 0].max()
w1_min, w1_max = f[:, 1].min(), f[:, 1].max()

w0list = np.linspace(w0_min, w0_max, 200)
w1list = np.linspace(w1_min, w1_max, 200)
W0arr, W1arr = np.meshgrid(w0list, w1list)
pos = np.dstack((W0arr, W1arr))

rv = multivariate_normal(mu, covAlpha)
Wpriorpdf = rv.pdf(pos)

print(f[:10])

pb.figure(figsize=(16, 12))
pb.contour(W0arr, W1arr, Wpriorpdf, 20, alpha=0.8)
pb.scatter(f[:, 0], f[:, 1], marker='o', color='black', alpha=0.5)
pb.title('Multivariate Normal Distribution with Samples')
pb.xlabel('X-axis')
pb.ylabel('Y-axis')
pb.axis('equal')

pb.xlim(-5, 5)
pb.ylim(-5, 5)

pb.grid(True)
pb.savefig('scatter_plot.png')
pb.close()

import numpy as np
import matplotlib.pyplot as plt
import os

os.makedirs("plots", exist_ok=True)
np.random.seed(42)

# Parameters
w_true = np.array([-1.2, 0.9])
sigma2 = 0.4  # Noise variance
alpha = 2.0   # Prior precision (not used for likelihood, kept for context)
n_values = [3, 5, 10, 20, 50, 100, 200, 700]
beta = 1 / sigma2
w0_range = np.linspace(-3, 3, 100)
w1_range = np.linspace(-3, 3, 100)

# Generate training inputs
x_train = np.linspace(-1, 1, 1000)
t_train = w_true[0] + w_true[1] * x_train + np.random.normal(0, np.sqrt(sigma2), x_train.shape)

# Function to compute likelihood
def compute_likelihood(x, t, w, beta):
    mu = w[0] + w[1] * x
    return np.prod([np.exp(-0.5 * beta * (t[i] - mu[i])**2) / np.sqrt(2 * np.pi / beta) for i in range(len(x))])

# Create subplots (2 rows, 4 columns)
fig, axes = plt.subplots(2, 4, figsize=(20, 10))
axes = axes.flatten()

for idx, n in enumerate(n_values):
    # Select random subset of training data
    indices = np.random.choice(len(x_train), n, replace=False)
    x_subset = x_train[indices]
    t_subset = t_train[indices]
    
    # Compute likelihood over weight grid
    likelihood = np.zeros((100, 100))
    for k, w0 in enumerate(w0_range):
        for m, w1 in enumerate(w1_range):
            likelihood[m, k] = compute_likelihood(x_subset, t_subset, [w0, w1], beta)
    likelihood /= likelihood.max()  # Normalize for visualization
    
    # Plot likelihood contours
    w0, w1 = np.meshgrid(w0_range, w1_range)
    axes[idx].contour(w0, w1, likelihood, levels=np.linspace(0.01, 1, 10))
    axes[idx].set_xlabel('w0')
    axes[idx].set_ylabel('w1')
    axes[idx].set_title(f'Likelihood (n={n})')
    axes[idx].grid(True)
    # Zoom in around true weights
    margin = 0.7
    axes[idx].set_xlim([-1.2 - margin, -1.2 + margin])
    axes[idx].set_ylim([0.9 - margin, 0.9 + margin])

# Adjust layout and save
fig.suptitle(f'Likelihood Variation with n (σ²={sigma2})')
plt.tight_layout(rect=[0, 0, 1, 0.95])
plt.savefig("plots/likelihood_n_variation.png")
plt.close(fig)

# Discussion
print("Discussion:")
print("- The subplots show the likelihood distribution for different n values, with fixed σ²=0.4.")
print("- For small n (e.g., 3, 5), the likelihood is broad, with wide contours, indicating high uncertainty in the weights due to limited data.")
print("- As n increases (e.g., 20, 50, 100, 200, 1000), the likelihood becomes sharper, with tighter contours centered near the true weights (w0=-1.2, w1=0.9).")
print("- Reason: More data points (higher n) provide more evidence, constraining the weights that best explain the data, making the likelihood more peaked.")
print("- This trend mirrors the posterior's behavior, as the likelihood drives the posterior's sharpening with more data.")
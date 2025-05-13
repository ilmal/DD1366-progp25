import numpy as np
import matplotlib.pyplot as plt
from scipy.stats import multivariate_normal
import os

os.makedirs("plots", exist_ok=True)

# Sätter np random till icke random så att vi kan få samma resultat varje gång
np.random.seed(42)

# Parameters for data generation
w_true = np.array([-1.2, 0.9])  # faktiska värden för [w0, w1]
sigma2_values = [0.1, 0.4, 0.8]  # Noise variances
alpha_values = [0.5, 2.0, 4.0]   # Prior precisions
n_train_samples = [3, 10, 20, 100]  # Training subset sizes
sigma2_default = 0.2  # default noise variance
alpha_default = 2.0   # default prior precision
beta_default = 1 / sigma2_default  # precision för likelihood

# Generate training and test data
x_train = np.linspace(-1, 1, 1000) # 1000 training samples mellan -1 till 1
print("X_TRAIN: ", x_train[:40], len(x_train))
x_test = np.concatenate([np.linspace(-1.5, -1.1, 50), np.linspace(1.1, 1.5, 50)]) # 100 test samples mellan -1.5 till -1.1 och 1.1 till 1.5
print("X_TEST: ", x_test[:40], len(x_test)) 
t_train = w_true[0] + w_true[1] * x_train + np.random.normal(0, np.sqrt(sigma2_default), x_train.shape) # t_train är en linjär funktion av x_train med lite noise (enligt formel i dokument)
print("T_TRAIN: ", t_train[40], len(t_train))
t_test = w_true[0] + w_true[1] * x_test + np.random.normal(0, np.sqrt(sigma2_default), x_test.shape) # t_test är en linjär funktion av x_test med lite noise 
print("T_TEXT: ", t_test[:40], len(t_test))

def compute_likelihood(x, t, w, beta):
    """
        Equation 17 L = p(t | X, w) = ∏ N(t_i | w^T x_i, β^(-1))
    where:
        N(t_i | w0 + w1 * x_i, 1/β) = sqrt(β / (2π)) * exp(-0.5 * β * (t_i - μ_i)^2)
    """
    mu = w[0] + w[1] * x

    return np.prod([np.exp(-0.5 * beta * (t[i] - mu[i])**2) / np.sqrt(2 * np.pi / beta) for i in range(len(x))])

# Function to compute posterior
def compute_posterior(x, t, alpha, beta):
    """
    Computes the posterior mean (m_N) and covariance (S_N) over weights using Equations 27 and 28.

    X_ext creates the extended input matrix [1, x], req for t = w0 + w1 * x

    S_N_inv = αI + β X_ext^T X_ext computes the inverse covariance (Equation 28), combining prior precision (α) and data information

    S_N = np.linalg.inv(S_N_inv) inverts to get the covariance.

    m_N = β S_N X_ext^T t computes the posterior mean (Equation 27), weighting the data by precision.

    """
    X_ext = np.vstack([np.ones_like(x), x]).T  # Extended input matrix
    S_N_inv = alpha * np.eye(2) + beta * X_ext.T @ X_ext
    S_N = np.linalg.inv(S_N_inv)
    m_N = beta * S_N @ X_ext.T @ t
    return m_N, S_N

# Function to compute maximum likelihood estimate
def compute_ml(x, t):
    """
    Computes the ML estimate of weights using Equation 21.

    X_ext creates the extended input matrix [1, x], req for t = w0 + w1 * x (same as above)

    w_ml = (X_ext^T X_ext)^(-1) X_ext^T t computes the weights that minimize the squared error.
    """

    X_ext = np.vstack([np.ones_like(x), x]).T
    w_ml = np.linalg.inv(X_ext.T @ X_ext) @ X_ext.T @ t
    return w_ml

# Function to compute predictive distribution
def compute_predictive(x, m_N, S_N, beta):
    """
    Computes the predictive mean and standard deviation for new inputs using Equations 33 and 34.

    mu_N = X_ext @ m_N computes the väntevärde ish.

    sigma_N2 = 1 / β + np.sum(X_ext @ S_N * X_ext, axis=1).

    """

    X_ext = np.vstack([np.ones_like(x), x]).T
    mu_N = X_ext @ m_N
    sigma_N2 = 1 / beta + np.sum(X_ext @ S_N * X_ext, axis=1)
    return mu_N, np.sqrt(sigma_N2)

# Function to plot distributions
def plot_distribution(dist, w0_range, w1_range, title, ax):
    w0, w1 = np.meshgrid(w0_range, w1_range)
    pos = np.dstack((w0, w1))
    Z = dist.pdf(pos)
    ax.contour(w0, w1, Z, levels=10)
    ax.set_xlabel('w0')
    ax.set_ylabel('w1')
    ax.set_title(title)
    ax.grid(True)

# Main loop for different configurations
for sigma2 in sigma2_values:
    continue
    beta = 1 / sigma2
    # Regenerate data with current sigma2
    t_train = w_true[0] + w_true[1] * x_train + np.random.normal(0, np.sqrt(sigma2), x_train.shape)
    t_test = w_true[0] + w_true[1] * x_test + np.random.normal(0, np.sqrt(sigma2), x_test.shape)
    
    for alpha in alpha_values:
        # Task 1.1: Prior distribution
        prior = multivariate_normal(mean=np.zeros(2), cov=np.eye(2) / alpha)
        fig, ax = plt.subplots(1, 3, figsize=(15, 5))
        plot_distribution(prior, np.linspace(-3, 1, 100), np.linspace(-2, 2, 100), f'Prior (α={alpha})', ax[0])
        
        for n in n_train_samples:
            # Select subset of training data
            indices = np.random.choice(len(x_train), n, replace=False)
            x_subset = x_train[indices]
            t_subset = t_train[indices]
            
            # Task 1.2: Likelihood
            w0_range = np.linspace(-3, 1, 100)
            w1_range = np.linspace(-2, 2, 100)
            likelihood = np.zeros((100, 100))
            for i, w0 in enumerate(w0_range):
                for j, w1 in enumerate(w1_range):
                    likelihood[j, i] = compute_likelihood(x_subset, t_subset, [w0, w1], beta)
            ax[1].contour(w0_range, w1_range, likelihood, levels=10)
            ax[1].set_title(f'Likelihood (n={n})')
            ax[1].set_xlabel('w0')
            ax[1].set_ylabel('w1')
            ax[1].grid(True)
            
            # Task 1.3: Posterior
            m_N, S_N = compute_posterior(x_subset, t_subset, alpha, beta)
            posterior = multivariate_normal(mean=m_N, cov=S_N)
            plot_distribution(posterior, w0_range, w1_range, f'Posterior (n={n})', ax[2])
            
            # Save distribution plots
            plt.tight_layout()
            plt.savefig(f"plots/distributions_sigma2_{sigma2}_alpha_{alpha}_n_{n}.png")
            plt.close(fig)
            
            # Task 1.4: Sample models from posterior
            fig2, ax2 = plt.subplots(figsize=(8, 6))
            for _ in range(5):
                w_sample = np.random.multivariate_normal(m_N, S_N)
                y = w_sample[0] + w_sample[1] * x_test
                ax2.plot(x_test, y, 'g-', alpha=0.3)
            
            # Plot training and test data
            ax2.scatter(x_subset, t_subset, c='b', label='Training data')
            ax2.scatter(x_test, t_test, c='r', label='Test data')
            
            # Task 1.5: Predictive distribution
            mu_N, sigma_N = compute_predictive(x_test, m_N, S_N, beta)
            ax2.plot(x_test, mu_N, 'k-', label='Predictive mean')
            ax2.fill_between(x_test, mu_N - sigma_N, mu_N + sigma_N, color='k', alpha=0.2, label='Predictive std')
            
            # Task 1.6: Maximum likelihood prediction
            w_ml = compute_ml(x_subset, t_subset)
            y_ml = w_ml[0] + w_ml[1] * x_test
            ax2.plot(x_test, y_ml, 'm--', label='ML prediction')
            
            ax2.set_xlabel('x')
            ax2.set_ylabel('t')
            ax2.set_title(f'Models (n={n}, α={alpha}, σ²={sigma2})')
            ax2.legend()
            ax2.grid(True)
            
            # Save model plot
            plt.tight_layout()
            plt.savefig(f"plots/models_sigma2_{sigma2}_alpha_{alpha}_n_{n}.png")
            plt.close(fig2)
        
# Task 1.7 and 1.8: Analysis
print("Task 1.7 Analysis:")
print("Effect of noise (σ²): Higher noise increases the spread of the posterior and predictive uncertainty.")
print("Effect of prior precision (α): Higher α tightens the prior, influencing the posterior to stay closer to zero.")
print("Effect of training samples (n): More samples sharpen the posterior, reducing uncertainty in predictions.")
print("\nTask 1.8 Comparison:")
print("Maximum Likelihood: Provides point estimates, ignoring uncertainty, sensitive to noise and sample size.")
print("Bayesian: Models uncertainty via posterior, more robust to noise, incorporates prior knowledge.")

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card shadow">
            <div class="card-body p-5">
                <h2 class="text-center mb-4">Create an Account</h2>
                
                <div id="alerts-container"></div>
                
                <form id="register-form">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        <div class="form-text mt-2">
                            Password must be at least 8 characters long
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Register</button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p>Already have an account? <a href="<?php echo APP_URL; ?>?route=login">Log in</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

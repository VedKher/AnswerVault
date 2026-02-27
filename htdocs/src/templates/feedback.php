<?php
// Feedback Page Template
?>
<div class="min-h-screen flex items-center justify-center py-20">
    <div class="max-w-2xl w-full mx-4">
        <!-- Feedback Card -->
        <div class="bg-gray-900/80 backdrop-blur-xl rounded-3xl border border-gray-800 shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-cyan-500/20 to-blue-600/20 p-8 border-b border-gray-800">
                <h1 class="text-3xl font-bold text-center bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 to-blue-500">
                    💬 Your Feedback Matters
                </h1>
                <p class="text-gray-400 text-center mt-2">Help us improve Answer Vault for everyone</p>
            </div>
            
            <!-- Form -->
            <form id="feedback-form" class="p-8 space-y-8">
                <!-- Star Rating -->
                <div class="text-center">
                    <label class="block text-sm font-medium text-gray-400 mb-4">How would you rate your experience?</label>
                    <div class="flex justify-center gap-2" id="star-rating">
                        <button type="button" data-rating="1" class="star-btn text-4xl text-gray-600 hover:text-yellow-400 transition-all duration-200 transform hover:scale-125">★</button>
                        <button type="button" data-rating="2" class="star-btn text-4xl text-gray-600 hover:text-yellow-400 transition-all duration-200 transform hover:scale-125">★</button>
                        <button type="button" data-rating="3" class="star-btn text-4xl text-gray-600 hover:text-yellow-400 transition-all duration-200 transform hover:scale-125">★</button>
                        <button type="button" data-rating="4" class="star-btn text-4xl text-gray-600 hover:text-yellow-400 transition-all duration-200 transform hover:scale-125">★</button>
                        <button type="button" data-rating="5" class="star-btn text-4xl text-gray-600 hover:text-yellow-400 transition-all duration-200 transform hover:scale-125">★</button>
                    </div>
                    <input type="hidden" name="rating" id="rating-value" value="0">
                    <p class="text-sm text-gray-500 mt-2" id="rating-text">Click to rate</p>
                </div>
                
                <!-- Feedback Text -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Tell us more (optional)</label>
                    <textarea 
                        name="message" 
                        id="feedback-message"
                        rows="4" 
                        placeholder="What do you like? What can we improve? Found any issues?"
                        class="w-full bg-gray-800/50 border border-gray-700 rounded-xl p-4 text-white placeholder-gray-500 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none resize-none transition-all"
                    ></textarea>
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit" 
                    id="submit-btn"
                    class="w-full py-4 px-6 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-xl shadow-lg hover:shadow-cyan-500/25 hover:scale-[1.02] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled
                >
                    <i class="fas fa-paper-plane mr-2"></i>Submit Feedback
                </button>
            </form>
            
            <!-- Success State (Hidden by default) -->
            <div id="success-message" class="hidden p-12 text-center">
                <div class="text-6xl mb-4">🎉</div>
                <h2 class="text-2xl font-bold text-white mb-2">Thank You!</h2>
                <p class="text-gray-400">Your feedback has been received. We truly appreciate it!</p>
                <a href="/" class="inline-block mt-6 px-6 py-3 bg-gray-800 hover:bg-gray-700 rounded-xl text-cyan-400 transition-colors">
                    <i class="fas fa-home mr-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    const stars = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('rating-value');
    const ratingText = document.getElementById('rating-text');
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('feedback-form');
    const successMessage = document.getElementById('success-message');
    
    const ratingTexts = ['', 'Poor 😞', 'Fair 😐', 'Good 🙂', 'Great 😊', 'Amazing! 🤩'];
    
    // Star rating logic
    stars.forEach(star => {
        star.addEventListener('click', () => {
            const rating = parseInt(star.dataset.rating);
            ratingInput.value = rating;
            ratingText.textContent = ratingTexts[rating];
            submitBtn.disabled = false;
            
            // Update star colors
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.remove('text-gray-600');
                    s.classList.add('text-yellow-400');
                } else {
                    s.classList.remove('text-yellow-400');
                    s.classList.add('text-gray-600');
                }
            });
        });
        
        // Hover preview
        star.addEventListener('mouseenter', () => {
            const rating = parseInt(star.dataset.rating);
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('text-yellow-300');
                }
            });
        });
        
        star.addEventListener('mouseleave', () => {
            stars.forEach((s) => {
                s.classList.remove('text-yellow-300');
            });
        });
    });
    
    // Form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
        
        const formData = new FormData();
        formData.append('rating', ratingInput.value);
        formData.append('message', document.getElementById('feedback-message').value);
        formData.append('page', document.referrer || window.location.href);
        
        try {
            const res = await fetch('/admin/feedback_api.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                form.classList.add('hidden');
                successMessage.classList.remove('hidden');
            } else {
                alert('Failed to submit feedback. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Submit Feedback';
            }
        } catch (err) {
            console.error(err);
            alert('Something went wrong. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Submit Feedback';
        }
    });
</script>

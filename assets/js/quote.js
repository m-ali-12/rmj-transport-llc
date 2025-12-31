<?php 
// Include configuration and header
include('includes/header.php'); 
?>

<!-- Main Content -->
<section class="quote-section">
    <div class="container">
        <h1 class="text-center mb-5">Car Shipment Quote Request</h1>
        
        <div class="quote-container">
            <div class="quote-form">
                <form id="shipmentQuoteForm" action="includes/quote-process.php" method="POST">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="fullName">Full Name*</label>
                            <input type="text" class="form-control" id="fullName" name="fullName" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Email Address*</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="phone">Phone Number*</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="vehicleType">Vehicle Type*</label>
                            <select class="form-control" id="vehicleType" name="vehicleType" required>
                                <option value="">Select Vehicle Type</option>
                                <option value="sedan">Sedan</option>
                                <option value="suv">SUV</option>
                                <option value="truck">Truck</option>
                                <option value="motorcycle">Motorcycle</option>
                                <option value="luxury">Luxury Vehicle</option>
                                <option value="classic">Classic Car</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="year">Vehicle Year*</label>
                            <input type="number" class="form-control" id="year" name="year" min="1900" max="<?php echo date('Y')+1; ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="make">Vehicle Make*</label>
                            <input type="text" class="form-control" id="make" name="make" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="model">Vehicle Model*</label>
                            <input type="text" class="form-control" id="model" name="model" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="condition">Vehicle Condition*</label>
                            <select class="form-control" id="condition" name="condition" required>
                                <option value="">Select Condition</option>
                                <option value="running">Running</option>
                                <option value="non-running">Non-Running</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="origin">Pickup Location (City, State, ZIP)*</label>
                        <input type="text" class="form-control" id="origin" name="origin" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="destination">Delivery Location (City, State, ZIP)*</label>
                        <input type="text" class="form-control" id="destination" name="destination" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipDate">Preferred Ship Date</label>
                        <input type="date" class="form-control" id="shipDate" name="shipDate">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="agreeTerms" name="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">I agree to the terms and conditions*</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Get Your Free Quote</button>
                </form>
            </div>
            
            <div class="quote-info">
                <h3>Why Choose MJ Hauling United?</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Fast & Reliable Service</li>
                    <li><i class="fas fa-check-circle"></i> Competitive Pricing</li>
                    <li><i class="fas fa-check-circle"></i> Fully Licensed & Insured</li>
                    <li><i class="fas fa-check-circle"></i> Door-to-Door Delivery</li>
                    <li><i class="fas fa-check-circle"></i> 24/7 Customer Support</li>
                </ul>
                
                <div class="contact-info">
                    <h4>Need Immediate Assistance?</h4>
                    <p><i class="fas fa-phone"></i> <a href="tel:+1234567890">(123) 456-7890</a></p>
                    <p><i class="fas fa-envelope"></i> <a href="mailto:info@mjhaulingunitedllc.com">info@mjhaulingunitedllc.com</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
// Include footer
include('includes/footer.php'); 
?>
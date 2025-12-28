/**
 * HotelOS - Number to Words Converter
 * Converts Indian Rupee amounts to words (English + Hindi support)
 * 
 * Usage:
 *   numberToWords(14750) → "Fourteen Thousand Seven Hundred Fifty Rupees Only"
 */

const NumberToWords = {
    ones: ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
           'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 
           'Seventeen', 'Eighteen', 'Nineteen'],
    
    tens: ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'],
    
    // Hindi words (for future use)
    hindiOnes: ['', 'एक', 'दो', 'तीन', 'चार', 'पाँच', 'छह', 'सात', 'आठ', 'नौ',
                'दस', 'ग्यारह', 'बारह', 'तेरह', 'चौदह', 'पंद्रह', 'सोलह',
                'सत्रह', 'अठारह', 'उन्नीस'],
    
    /**
     * Convert number to words (Indian format with Lakh/Crore)
     * @param {number} num - Amount in rupees
     * @param {boolean} includePaise - Include paise if present
     * @returns {string} Amount in words
     */
    convert(num, includePaise = true) {
        if (num === 0) return 'Zero Rupees Only';
        if (num < 0) return 'Minus ' + this.convert(-num);
        
        // Split into rupees and paise
        const rupees = Math.floor(num);
        const paise = Math.round((num - rupees) * 100);
        
        let words = this.convertRupees(rupees);
        
        if (words) {
            words += ' Rupees';
        }
        
        if (paise > 0 && includePaise) {
            words += ' and ' + this.convertTwoDigits(paise) + ' Paise';
        }
        
        return words + ' Only';
    },
    
    /**
     * Convert rupees part (Indian numbering system)
     */
    convertRupees(num) {
        if (num === 0) return '';
        
        let words = '';
        
        // Crores (1,00,00,000+)
        if (num >= 10000000) {
            words += this.convertTwoDigits(Math.floor(num / 10000000)) + ' Crore ';
            num %= 10000000;
        }
        
        // Lakhs (1,00,000 - 99,99,999)
        if (num >= 100000) {
            words += this.convertTwoDigits(Math.floor(num / 100000)) + ' Lakh ';
            num %= 100000;
        }
        
        // Thousands (1,000 - 99,999)
        if (num >= 1000) {
            words += this.convertTwoDigits(Math.floor(num / 1000)) + ' Thousand ';
            num %= 1000;
        }
        
        // Hundreds (100 - 999)
        if (num >= 100) {
            words += this.ones[Math.floor(num / 100)] + ' Hundred ';
            num %= 100;
        }
        
        // Tens and Ones (1 - 99)
        if (num > 0) {
            words += this.convertTwoDigits(num);
        }
        
        return words.trim();
    },
    
    /**
     * Convert two-digit number to words
     */
    convertTwoDigits(num) {
        if (num < 20) {
            return this.ones[num];
        }
        
        const ten = Math.floor(num / 10);
        const one = num % 10;
        
        return this.tens[ten] + (one > 0 ? ' ' + this.ones[one] : '');
    },
    
    /**
     * Format number in Indian numbering system (12,34,567)
     */
    formatIndian(num) {
        const str = num.toString();
        let result = '';
        let count = 0;
        
        for (let i = str.length - 1; i >= 0; i--) {
            if (count === 3 || (count > 3 && (count - 3) % 2 === 0)) {
                result = ',' + result;
            }
            result = str[i] + result;
            count++;
        }
        
        return result;
    }
};

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NumberToWords;
}

// Global function for easy access
function numberToWords(amount) {
    return NumberToWords.convert(amount);
}

function formatIndianNumber(num) {
    return NumberToWords.formatIndian(num);
}

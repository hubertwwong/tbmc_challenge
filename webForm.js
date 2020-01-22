/**
 * This a JavaScript class that manages a web form, including functionality for validating fields and submitting form values.
 * The template of the web form is not included, nor is it needed to complete this activity.
 *
 * Instructions:
 *
 * (1) Implement the validateForm() function.
 *      - Should return a boolean: return true if the form has valid responses, false otherwise
 *      - Should utilize FormValidator.validate() to validate the form fields
 *          (documentation on FormValidator can be found at the bottom of this file)
 *      - If the validate() function returns with validation errors, display the errors in a browser alert
 *      - If the validate() function returns with a failed promise (meaning the API is not available at the moment),
 *          display a browser alert stating "Sorry, please submit this form at a later time."
 *
 *  (2) Can you refactor submitForm() so that it waits to reset the form after we are sure the form responses have been
 *      successfully received by the API?
 * 
 *      I'm putting async await to wait for the API response to wait for it to complete before the reset occurs. 
 *
 *  (3) What is wrong with how we initially set "responses" to the default values of "emptyForm" as well as the implementation
 *      of resetForm()? Can you refactor the inital setting of responses and/or the resetForm() function to achieve the
 *      desired behavior?
 * 
 *      I think the issue with this is that you setting responses to a reference of emptyForm.
 *      Since its reactive, I think when a user types in values in the form, the emptyForm object will not be empty any more.
 *      Another issue is that Object assign is a shallow copy. I think address would be a reference.
 */

import FormValidator from 'validator-lib';
import HttpClient from 'http-lib';

class Form {
    private $validator = new FormValidator();
    private emptyForm = {
        name: '',
        address: {
            street1: '',
            street2: '',
            city: '',
            state: '',
            zip: '',
        },
    };

    // Use to create a clean copy for of the emptyForm.
    private cloneEmptyForm() {
        // shallow copy.
        let newForm = Object.assign({}, this.emptyForm);
        // clone the address field.
        newForm.address = Object.assign({}, this.emptyForm.address);
        return newForm;
    }

    // Assume this is reactive (i.e. if the user updates the form fields in the UI, this object is updated accordingly)
    private responses = this.cloneEmptyForm();

    private async validateForm() {
        try {
            // Wait for the form validation function to complete.
            let res = await FormValidator.validate();

            // display error in alert.
            if (!res.valid) {
                window.alert(JSON.stringify(res.errors));
            }

            // Return the response from validate. It can either be true for false.
            return res.valid;
        } catch(err) {
            windows.alert("Sorry, please submit this form at a later time.");
            return Promise.reject(new Error(err));
        }
    }

    private async submitForm() {
        try {
            // Wait for a response from validateForm.
            // If the promsie fails, it should go to the catch block.
            let res = await this.validateForm();
            if (res) {
                // Wait for the post to complete before resetting it.
                try {
                    // I'm assuming if the post request fails, the promise will be rejected.
                    let res = await HttpClient.post('https://api.example.com/form/', this.responses);
                    // If not, we can check here on the res variable.
                    this.resetForm();
                    return "Form submitted";
                } catch(err) {
                    return Promise.reject(new Error(err));
                }
            }
        } catch(err) {
            return Promise.reject(new Error(err));
        }
    }

    private resetForm() {
        this.responses = this.cloneEmptyForm();
    }
}

/**
 * FormValidator class
 *
 * Methods:
 *  validate()
 *
 *  - Makes a call to the API to ensure that the form responses are valid
 *  - Returns a Promise that, on resolve, returns an object of the structure:
 *      {
 *          valid: boolean;
 *          errors: string[];
 *      }
 *  - Note: Potentially can return in a Promise reject in the case the API is not available in that moment
 */

import { $, $$ } from './util';
import { validate, clearOnInput, send, applyServerErrors, setBusy } from './forms';
import { initBranchFinder } from './branch-finder';

export function initEnquiryForms() {
    $$('[data-enquiry-form]').forEach((form) => {
        const submit = $('[data-submit]', form);
        const formError = $('[data-form-error]', form);
        const success = form.parentElement.querySelector('[data-enquiry-success]');

        clearOnInput(form);
        // Back from "Find the nearest café": typed details restored, café chosen.
        if (initBranchFinder(form)) $('[data-branch-found]', form)?.scrollIntoView({ block: 'center' });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            formError.hidden = true;

            const invalid = validate(form);
            if (invalid.length) {
                $(`[name="${invalid[0]}"]`, form)?.focus();
                return;
            }

            setBusy(submit, true, form.dataset.msgSending);
            try {
                const { ok, status, data } = await send(form);
                if (ok) {
                    form.hidden = true;
                    success.hidden = false;
                    success.focus();
                    return;
                }
                if (status === 422) {
                    $(`[name="${applyServerErrors(form, data.errors)}"]`, form)?.focus();
                    return;
                }
                throw new Error(String(status));
            } catch {
                formError.textContent = form.dataset.msgError;
                formError.hidden = false;
            } finally {
                setBusy(submit, false);
            }
        });
    });
}

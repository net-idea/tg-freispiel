import { Controller } from '@hotwired/stimulus';

interface SubmissionResponse {
  success: boolean;
  message: string;
  errors?: Record<string, string[]>;
}

/**
 * Submits a Symfony form via fetch and renders the JSON result inline
 * (success banner, per-field validation errors). The field prefix is derived
 * from the form's name attribute, so any form can use this controller.
 * Without JavaScript the form falls back to a regular POST handled
 * server-side.
 */
export default class extends Controller<HTMLElement> {
  static targets = ['form', 'alert', 'submit'];

  declare readonly formTarget: HTMLFormElement;
  declare readonly alertTarget: HTMLElement;
  declare readonly submitTarget: HTMLButtonElement;

  private submitLabel = '';

  async submit(event: Event): Promise<void> {
    event.preventDefault();

    this.clearFeedback();
    this.setBusy(true);

    try {
      const response = await fetch(this.formTarget.action, {
        method: 'POST',
        body: new FormData(this.formTarget),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      const result = (await response.json()) as SubmissionResponse;

      if (result.success) {
        this.showAlert('success', result.message);
        this.formTarget.hidden = true;
      } else {
        this.showAlert('danger', result.message);
        this.showFieldErrors(result.errors ?? {});
      }
    } catch {
      this.showAlert('danger', 'Es ist ein Fehler aufgetreten. Bitte versuche es in Kürze erneut.');
    } finally {
      this.setBusy(false);
    }
  }

  private showAlert(kind: 'success' | 'danger', message: string): void {
    const alert = document.createElement('div');
    alert.className = `alert alert-${kind} mb-4`;
    alert.setAttribute('role', 'alert');
    alert.textContent = message;
    this.alertTarget.replaceChildren(alert);
    this.alertTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  private showFieldErrors(errors: Record<string, string[]>): void {
    const formName = this.formTarget.getAttribute('name') ?? '';

    for (const [field, messages] of Object.entries(errors)) {
      // Multi-value fields (checkbox groups) render as name="...[field][]"
      const widget = this.formTarget.querySelector<HTMLElement>(`[name="${formName}[${field}]"]`) ?? this.formTarget.querySelector<HTMLElement>(`[name="${formName}[${field}][]"]`);

      if (!widget) {
        continue;
      }

      widget.classList.add('is-invalid');

      const feedback = document.createElement('div');
      feedback.className = 'invalid-feedback d-block';
      feedback.textContent = messages.join(' ');
      widget.closest('.mb-4')?.appendChild(feedback);
    }
  }

  private clearFeedback(): void {
    this.alertTarget.replaceChildren();
    this.formTarget.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    this.formTarget.querySelectorAll('.invalid-feedback').forEach((el) => el.remove());
  }

  private setBusy(busy: boolean): void {
    if (busy) {
      this.submitLabel = this.submitTarget.textContent ?? '';
    }

    this.submitTarget.disabled = busy;
    this.submitTarget.textContent = busy ? 'Wird gesendet…' : this.submitLabel;
  }
}

(function () {
  const d: Document = document;

  const emailUser: number[] = [97, 100, 97, 109];
  const emailDomain: number[] = [110, 101, 116, 45, 105, 46, 100, 101];
  const phoneNationalCharCodes: number[] = [48, 49, 53, 55, 55, 47, 49, 54, 55, 49, 51, 53, 51];

  function fromCodes(arr: number[]): string {
    return String.fromCharCode.apply(null, arr as unknown as number[]);
  }

  function normalizePhoneToE164Digits(raw: string, countryCallingCodeDigits: string): string {
    const cleaned = (raw || '').trim();
    if (!cleaned) return '';
    const hasPlus = cleaned.startsWith('+');
    const digitsOnly = cleaned.replace(/\D+/g, '');
    if (!digitsOnly) return '';
    if (hasPlus) return digitsOnly;
    if (digitsOnly.startsWith('00')) return digitsOnly.slice(2);
    if (digitsOnly.startsWith(countryCallingCodeDigits)) return digitsOnly;
    const national = digitsOnly.replace(/^0+/, '');
    return countryCallingCodeDigits + national;
  }

  function formatGermanDisplayFromNational(rawNational: string): string {
    const digits = (rawNational || '').replace(/\D+/g, '');
    const national = digits.replace(/^0+/, '');
    if (national.length >= 5) {
      const block1 = national.slice(0, 4);
      const rest = national.slice(4);
      return `+49 ${block1} / ${rest}`;
    }
    return `+49 ${national}`.trim();
  }

  const email: string = fromCodes(emailUser) + '@' + fromCodes(emailDomain);

  // Telefonnummer dekodieren und Formate ableiten
  const rawPhoneNational: string = fromCodes(phoneNationalCharCodes); // z.B. 01577/1671353
  const phoneText: string = formatGermanDisplayFromNational(rawPhoneNational);
  const phoneE164Digits: string = normalizePhoneToE164Digits(rawPhoneNational, '49');
  const phoneTel: string = phoneE164Digits ? `+${phoneE164Digits}` : '';

  // Email links (supports multiple)
  const emailElements = Array.from(d.querySelectorAll<HTMLElement>('.contact-email'));
  const emailElementById = d.getElementById('contact-email');

  if (emailElements.length === 0 && emailElementById) emailElements.push(emailElementById);

  emailElements.forEach((emailElement) => {
    const a: HTMLAnchorElement = d.createElement('a');
    a.href = 'mailto:' + email;
    a.textContent = email;
    a.rel = 'nofollow';
    emailElement.replaceWith(a);
  });

  // Phone links (supports multiple)
  const phoneElements = Array.from(d.querySelectorAll<HTMLElement>('.contact-phone'));
  const phoneElementById = d.getElementById('contact-phone');

  if (phoneElements.length === 0 && phoneElementById) phoneElements.push(phoneElementById);

  phoneElements.forEach((phoneElement) => {
    const a: HTMLAnchorElement = d.createElement('a');

    if (phoneTel) {
      a.href = 'tel:' + phoneTel;
    }

    a.textContent = phoneText;
    a.rel = 'nofollow';
    phoneElement.replaceWith(a);
  });

  // WhatsApp links (supports multiple) - fetch SVG once
  const whatsappElements = Array.from(d.querySelectorAll<HTMLElement>('.contact-whatsapp'));
  const whatsappElementById = d.getElementById('contact-whatsapp');

  if (whatsappElements.length === 0 && whatsappElementById) {
    whatsappElements.push(whatsappElementById);
  }

  if (whatsappElements.length > 0 && phoneE164Digits) {
    // wa.me guideline: digits-only, international format, no leading zeros, no +, no separators.
    const whatsappNumber: string = phoneE164Digits;

    const createAnchor = (): HTMLAnchorElement => {
      const whatsappAnchor: HTMLAnchorElement = d.createElement('a');
      whatsappAnchor.href = 'https://wa.me/' + whatsappNumber;
      whatsappAnchor.target = '_blank';
      whatsappAnchor.rel = 'nofollow noopener';

      return whatsappAnchor;
    };

    whatsappElements.forEach((whatsappElement) => {
      const whatsappAnchor = createAnchor();
      // preserve classes from placeholder (so btn styles remain)
      whatsappAnchor.className = whatsappElement.className;

      // if the placeholder had label text, keep it alongside the icon
      const label = (whatsappElement.textContent || '').trim();

      const img = d.createElement('img');
      img.src = '/images/whatsapp.svg';
      img.alt = 'WhatsApp';
      img.style.height = '2em';
      img.style.width = 'auto';
      img.style.marginRight = '0.5em';
      img.style.verticalAlign = 'middle';

      whatsappAnchor.appendChild(img);

      if (label) whatsappAnchor.appendChild(d.createTextNode(' ' + label));

      whatsappElement.replaceWith(whatsappAnchor);
    });
  }
})();

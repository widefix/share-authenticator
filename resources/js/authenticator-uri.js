const cleanLabel = (value, field) => {
    const cleaned = String(value ?? '').trim().replace(/\s+/gu, ' ');

    if (!cleaned || cleaned.length > 120 || cleaned.includes(':')) {
        throw new Error(`${field} is required, may not contain a colon, and must be 120 characters or fewer.`);
    }

    return cleaned;
};

export const canonicalSecret = (value) => {
    const secret = String(value ?? '').trim().replace(/\s+/gu, '').replace(/=+$/u, '').toUpperCase();

    if (!secret || secret.length > 256 || !/^[A-Z2-7]+$/u.test(secret)) {
        throw new Error('Enter a valid Base32 secret.');
    }

    return secret;
};

// otpauth:// is not a special scheme (https://url.spec.whatwg.org/#special-scheme), so
// engines are not required to parse an authority/host component for it the way they do
// for http(s) — WebKit in particular has been inconsistent about it (e.g. `totp` landing
// in the pathname instead of the hostname). Parse the otpauth://TYPE/LABEL?QUERY structure
// from the text directly instead of relying on URL#hostname to carry the type.
const OTPAUTH_PATTERN = /^otpauth:\/\/([^/?#]+)\/([^?#]*)(?:\?([^#]*))?/iu;

export const parseOtpAuthUri = (value) => {
    const normalized = String(value).replace(/[\t\r\n]+/gu, '').trim();
    const match = OTPAUTH_PATTERN.exec(normalized);

    if (!match) {
        throw new Error('Enter a valid otpauth://totp URI.');
    }

    const [, type, rawLabel, rawQuery] = match;

    if (type.toLowerCase() !== 'totp') {
        throw new Error('Only time-based otpauth://totp URIs are supported.');
    }

    const label = decodeURIComponent(rawLabel);
    const colon = label.indexOf(':');
    const labelIssuer = colon >= 0 ? label.slice(0, colon).trim() : '';
    const account = colon >= 0 ? label.slice(colon + 1).trim() : label.trim();
    const params = new URLSearchParams(rawQuery ?? '');
    const queryIssuer = (params.get('issuer') ?? '').trim();

    if (labelIssuer && queryIssuer && labelIssuer !== queryIssuer) {
        throw new Error('The issuer in the label and issuer parameter must match.');
    }

    const algorithm = (params.get('algorithm') ?? 'SHA1').toUpperCase();
    const digits = Number(params.get('digits') ?? 6);
    const period = Number(params.get('period') ?? 30);

    if (!['SHA1', 'SHA256', 'SHA512'].includes(algorithm)) throw new Error('Choose SHA1, SHA256, or SHA512.');
    if (![6, 8].includes(digits)) throw new Error('Codes must contain 6 or 8 digits.');
    if (!Number.isInteger(period) || period < 15 || period > 300) throw new Error('The period must be between 15 and 300 seconds.');

    return {
        issuer: cleanLabel(queryIssuer || labelIssuer, 'Issuer'),
        account: cleanLabel(account, 'Account'),
        secret: canonicalSecret(params.get('secret')),
        algorithm,
        digits,
        period,
    };
};

export const validateManualAuthenticator = (fields) => ({
    issuer: cleanLabel(fields.issuer, 'Issuer'),
    account: cleanLabel(fields.account, 'Account'),
    secret: canonicalSecret(fields.secret),
    algorithm: ['SHA1', 'SHA256', 'SHA512'].includes(fields.algorithm) ? fields.algorithm : 'SHA1',
    digits: [6, 8].includes(Number(fields.digits)) ? Number(fields.digits) : 6,
    period: Number(fields.period) >= 15 && Number(fields.period) <= 300 ? Number(fields.period) : 30,
});

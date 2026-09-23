import { describe, expect, it } from 'vitest';
import { canonicalSecret, parseOtpAuthUri } from './authenticator-uri.js';

describe('otpauth URI parsing', () => {
    it('uses interoperable defaults', () => {
        expect(parseOtpAuthUri('otpauth://totp/Example%3Aalice%40example.com?secret=JBSWY3DPEHPK3PXP&issuer=Example')).toEqual({
            issuer: 'Example', account: 'alice@example.com', secret: 'JBSWY3DPEHPK3PXP', algorithm: 'SHA1', digits: 6, period: 30,
        });
    });

    it('supports SHA256, eight digits, and a custom period', () => {
        const parsed = parseOtpAuthUri('otpauth://totp/Service%3Aaccount?secret=JBSWY3DPEHPK3PXP&issuer=Service&algorithm=SHA256&digits=8&period=45');
        expect(parsed).toMatchObject({ algorithm: 'SHA256', digits: 8, period: 45 });
    });

    it('rejects HOTP and conflicting issuers', () => {
        expect(() => parseOtpAuthUri('otpauth://hotp/Service:a?secret=JBSWY3DPEHPK3PXP&issuer=Service&counter=1')).toThrow(/time-based/u);
        expect(() => parseOtpAuthUri('otpauth://totp/One:a?secret=JBSWY3DPEHPK3PXP&issuer=Two')).toThrow(/must match/u);
    });

    it('parses a dotted domain-like label without mistaking it for the host (issue #3)', () => {
        const uri = 'otpauth://totp/phabricator.stagingdns.co.uk:andrey?secret=QWNF56TSWTLZEA5YKPVBTX2Y27QBIJWX&issuer=phabricator.stagingdns.co.uk';
        expect(parseOtpAuthUri(uri)).toEqual({
            issuer: 'phabricator.stagingdns.co.uk', account: 'andrey', secret: 'QWNF56TSWTLZEA5YKPVBTX2Y27QBIJWX', algorithm: 'SHA1', digits: 6, period: 30,
        });
    });

    it('accepts an uppercase scheme and strips whitespace pasted mid-URI', () => {
        const uri = 'OTPAUTH://TOTP/Example:alice@example.com?secret=JBSWY3DPEHPK3PXP&issuer=Example';
        expect(parseOtpAuthUri(`  ${uri.slice(0, 20)}\n${uri.slice(20)}  `)).toMatchObject({ issuer: 'Example', account: 'alice@example.com' });
    });

    it('rejects non-otpauth input', () => {
        expect(() => parseOtpAuthUri('https://example.com')).toThrow(/valid otpauth/u);
        expect(() => parseOtpAuthUri('not a uri at all')).toThrow(/valid otpauth/u);
    });

    it('canonicalizes padded and spaced Base32', () => {
        expect(canonicalSecret('jbsw y3dp ehpk3pxp====')).toBe('JBSWY3DPEHPK3PXP');
    });
});

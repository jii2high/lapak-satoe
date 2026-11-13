import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { send as sendOtp, verify as verifyOtp } from '@/routes/account/otp';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onVerified?: () => void;
};

export function EmailVerificationDialog({
    open,
    onOpenChange,
    onVerified,
}: Props) {
    const { auth } = usePage().props;
    const [code, setCode] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [sending, setSending] = useState(false);
    const [verifying, setVerifying] = useState(false);
    const [remaining, setRemaining] = useState(0);
    const wasOpen = useRef(false);

    const sendCode = useCallback(() => {
        setSending(true);
        setError(null);

        router.post(
            sendOtp.url(),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                // shortcut: mirrors config/lapak.php otp.resend_seconds, hardcoded
                onSuccess: () => setRemaining(60),
                onError: (errors) =>
                    setError(errors.code ?? 'Kode gagal dikirim. Coba lagi.'),
                onFinish: () => setSending(false),
            },
        );
    }, []);

    useEffect(() => {
        if (open && !wasOpen.current && remaining === 0) {
            sendCode();
        }

        if (!open) {
            setCode('');
            setError(null);
        }

        wasOpen.current = open;
    }, [open, remaining, sendCode]);

    useEffect(() => {
        if (remaining <= 0) {
            return;
        }

        const timer = window.setTimeout(
            () => setRemaining((value) => Math.max(0, value - 1)),
            1000,
        );

        return () => window.clearTimeout(timer);
    }, [remaining]);

    const verify = () => {
        setVerifying(true);
        setError(null);

        router.post(
            verifyOtp.url(),
            { code },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    onOpenChange(false);
                    onVerified?.();
                },
                onError: (errors) => {
                    setError(errors.code ?? 'Kode tidak valid. Coba lagi.');
                    setCode('');
                },
                onFinish: () => setVerifying(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="font-display text-xl">
                        Verifikasi email
                    </DialogTitle>
                    <DialogDescription>
                        Kami kirim kode 6 angka ke {auth.user.email_masked}.
                        Kode berlaku 5 menit.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-3">
                    <Label htmlFor="otp-code">Kode dari email</Label>
                    <InputOTP
                        id="otp-code"
                        maxLength={6}
                        value={code}
                        onChange={setCode}
                        inputMode="numeric"
                        containerClassName="justify-center"
                    >
                        <InputOTPGroup className="gap-2">
                            {[0, 1, 2, 3, 4, 5].map((index) => (
                                <InputOTPSlot
                                    key={index}
                                    index={index}
                                    className="h-[52px] w-11 rounded-md border text-xl"
                                />
                            ))}
                        </InputOTPGroup>
                    </InputOTP>

                    <InputError message={error ?? undefined} />

                    <p
                        className="text-center text-xs text-muted-foreground"
                        aria-live="polite"
                    >
                        {remaining > 0
                            ? `Kirim ulang kode dalam ${remaining} detik`
                            : 'Belum menerima kode? Kirim ulang.'}
                    </p>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Nanti saja
                    </Button>
                    <Button
                        type="button"
                        variant="secondary"
                        disabled={remaining > 0 || sending}
                        onClick={sendCode}
                    >
                        {sending && <Spinner />}
                        Kirim ulang kode
                    </Button>
                    <Button
                        type="button"
                        disabled={code.length !== 6 || verifying}
                        onClick={verify}
                    >
                        {verifying && <Spinner />}
                        Verifikasi email
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

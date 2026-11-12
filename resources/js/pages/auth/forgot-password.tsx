import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <Head title="Lupa kata sandi" />

            {status && (
                <div className="rounded-md border border-primary/30 bg-secondary px-3 py-2 text-sm text-secondary-foreground">
                    {status}
                </div>
            )}

            <Form {...email.form()} className="grid gap-6">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                autoFocus
                                placeholder="budi@email.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <Button
                            className="w-full"
                            disabled={processing}
                            data-test="email-password-reset-link-button"
                        >
                            {processing && <Spinner />}
                            Kirim tautan
                        </Button>
                    </>
                )}
            </Form>

            <p className="text-center text-sm text-muted-foreground">
                Ingat kata sandimu?{' '}
                <TextLink href={login()}>Kembali ke halaman masuk</TextLink>
            </p>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Lupa kata sandi',
    description:
        'Masukkan email akunmu. Kami kirim tautan untuk mengatur ulang kata sandi.',
};

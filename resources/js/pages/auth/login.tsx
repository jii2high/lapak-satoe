import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Masuk" />

            <Form
                action={store.url()}
                method="post"
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        {status && (
                            <div className="rounded-md border border-primary/30 bg-secondary px-3 py-2 text-sm text-secondary-foreground">
                                {status}
                            </div>
                        )}

                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="login">
                                    Username atau email
                                </Label>
                                <Input
                                    id="login"
                                    type="text"
                                    name="login"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="username"
                                    placeholder="budi atau budi@email.com"
                                />
                                <InputError message={errors.login} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="password">Kata sandi</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="text-sm"
                                            tabIndex={4}
                                        >
                                            Lupa kata sandi?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Kata sandi"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex min-h-11 items-center gap-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Ingat saya</Label>
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                tabIndex={5}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Masuk
                            </Button>
                        </div>

                        <p className="text-center text-sm text-muted-foreground">
                            Belum punya akun?{' '}
                            <TextLink href={register()} tabIndex={6}>
                                Daftar
                            </TextLink>
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Masuk',
    description: 'Masuk untuk melihat keranjang dan pesananmu.',
};

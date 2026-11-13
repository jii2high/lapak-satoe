import { Head } from '@inertiajs/react';

type Props = {
    content: string;
};

export default function Terms({ content }: Props) {
    return (
        <>
            <Head title="Syarat dan ketentuan" />

            <article
                className="prose-lapak mx-auto max-w-3xl"
                dangerouslySetInnerHTML={{ __html: content }}
            />
        </>
    );
}

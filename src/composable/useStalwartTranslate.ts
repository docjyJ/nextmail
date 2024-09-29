import { translate, translatePlural } from '@nextcloud/l10n'

export default function useStalwartTranslate(app = 'stalwart') {
	return {
		t: (text: string) => translate(app, text),
		n: (textSingular: string, textPlural: string, number: number) => translatePlural(app, textSingular, textPlural, number),
	}
}

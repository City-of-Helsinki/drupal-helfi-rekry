import { Button, RadioButton, TextInput } from 'hds-react';
import { useAtom } from 'jotai';
import { useEffect } from 'react';

import RadioOptions from '../enum/RadioOptions';
import SearchComponents from '../enum/SearchComponents';
import { keywordAtom, radioAtom } from '../store';
import { urlAtom } from '../store';

const FormContainer = () => {
  const [radio, setRadio] = useAtom(radioAtom);
  const [keyword, setKeyword] = useAtom(keywordAtom);
  const [urlParams, setUrlParams] = useAtom(urlAtom);

  useEffect(() => {
    setKeyword(urlParams?.keyword || '');
  }, []);

  const onSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setUrlParams({ ...urlParams, keyword: keyword, [SearchComponents.RADIO_OPTIONS]: radio });
  };

  const onKeywordChange = (event: any) => {
    setKeyword(event.target.value);
  };

  return (
    <form onSubmit={onSubmit}>
      <TextInput id={SearchComponents.KEYWORD} label='text' onChange={onKeywordChange} value={keyword} />
      <fieldset>
        <legend>{Drupal.t('Show only')}</legend>
        <RadioButton
          label={Drupal.t('Continuous')}
          id={SearchComponents.RADIO_OPTIONS}
          onClick={() => setRadio(RadioOptions.CONTINUOUS)}
          checked={!!radio}
        />
        <Button type='submit'>Submit</Button>
      </fieldset>
    </form>
  );
};

export default FormContainer;

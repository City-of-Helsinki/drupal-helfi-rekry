import { Button, RadioButton, Select, TextInput } from 'hds-react';
import { useAtom, useAtomValue } from 'jotai';
import { useUpdateAtom } from 'jotai/utils';
import React, { useEffect } from 'react';

import RadioOptions from '../enum/RadioOptions';
import SearchComponents from '../enum/SearchComponents';
import { keywordAtom, radioAtom, urlUpdateAtom } from '../store';
import { occupationSelectionAtom, occupationsAtom, urlAtom } from '../store';
import type OptionType from '../types/OptionType';
import type URLParams from '../types/URLParams';

const FormContainer = () => {
  const [radio, setRadio] = useAtom(radioAtom);
  const [keyword, setKeyword] = useAtom(keywordAtom);
  const urlParams = useAtomValue(urlAtom);
  const occupations = useAtomValue(occupationsAtom);
  const setUrlParams = useUpdateAtom(urlUpdateAtom);
  const [occupationFilter, setOccupationFilter] = useAtom(occupationSelectionAtom);

  // Set form control values from url parameters on load
  useEffect(() => {
    setKeyword(urlParams?.keyword || '');
    let defaultOccupation = undefined;
    if (urlParams.occupation) {
      defaultOccupation = occupations.find(({ value }) => value === urlParams.occupation);
      setOccupationFilter(defaultOccupation as OptionType);
    }
  }, []);

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setUrlParams({
      ...urlParams,
      keyword: keyword,
      [SearchComponents.RADIO_OPTIONS]: radio,
      occupation: occupationFilter?.value,
    } as URLParams);
  };

  const handleKeywordChange = ({ target: { value } }: { target: { value: string } }) => setKeyword(value);

  const handleOccupationsChange = (option: OptionType) => setOccupationFilter(option);

  return (
    <form onSubmit={handleSubmit}>
      <fieldset>
        <TextInput id={SearchComponents.KEYWORD} label='text' onChange={handleKeywordChange} value={keyword} />
      </fieldset>
      <fieldset>
        <Select
          label={Drupal.t('Ammattikunta', { context: 'Occupations filter label' })}
          helper={Drupal.t('ammattikunta - a18n', { context: 'Occupations filter helper' })}
          options={occupations}
          value={occupationFilter}
          id={SearchComponents.OCCUPATIONS}
          onChange={handleOccupationsChange}
        />
      </fieldset>
      <fieldset>
        <legend>{Drupal.t('Show only', { context: 'Show only- filters legend' })}</legend>
        <RadioButton
          label={Drupal.t('Continuous', { context: 'Continuous jobs filter label' })}
          id={SearchComponents.RADIO_OPTIONS}
          onClick={() => setRadio(RadioOptions.CONTINUOUS)}
          checked={!!radio}
        />
        <Button type='submit'>{Drupal.t('Submit', { context: 'Rekry Search Submit button' })}</Button>
      </fieldset>
    </form>
  );
};

export default FormContainer;

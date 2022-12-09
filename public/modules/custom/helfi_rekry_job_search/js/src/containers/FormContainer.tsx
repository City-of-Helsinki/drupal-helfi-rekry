import { Button, Checkbox, Select, TextInput } from 'hds-react';
import { useAtom, useAtomValue } from 'jotai';
import { useUpdateAtom } from 'jotai/utils';
import React, { useEffect } from 'react';

import SearchComponents from '../enum/SearchComponents';
import {
  continuousAtom,
  internshipAtom,
  keywordAtom,
  occupationSelectionAtom,
  occupationsAtom,
  summerJobsAtom,
  urlUpdateAtom,
  youthSummerJobsAtom,
} from '../store';
import { urlAtom } from '../store';
import { CONTINUOUS, INTERNSHIPS, SUMMER_JOBS, YOUTH_SUMMER_JOBS } from '../translations';
import type OptionType from '../types/OptionType';
import type URLParams from '../types/URLParams';
import SelectionsContainer from './SelectionsContainer';

const FormContainer = () => {
  const [continuous, setContinuous] = useAtom(continuousAtom);
  const [internship, setInternship] = useAtom(internshipAtom);
  const [summerJobs, setSummerJobs] = useAtom(summerJobsAtom);
  const [youthSummerJobs, setYouthSummerJobs] = useAtom(youthSummerJobsAtom);
  const [keyword, setKeyword] = useAtom(keywordAtom);
  const urlParams = useAtomValue(urlAtom);
  const setUrlParams = useUpdateAtom(urlUpdateAtom);
  const [occupationSelection, setOccupationFilter] = useAtom(occupationSelectionAtom);
  const occupationsOptions = useAtomValue(occupationsAtom);

  // Set form control values from url parameters on load
  useEffect(() => {
    setKeyword(urlParams?.keyword || '');
    setOccupationFilter(urlParams?.occupations || []);
    setContinuous(!!urlParams?.continuous);
    setInternship(!!urlParams?.internship);
    setSummerJobs(!!urlParams?.summerJobs);
    setYouthSummerJobs(!!urlParams?.youthSummerJobs);
  }, []);

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setUrlParams({
      keyword,
      continuous,
      internship,
      occupations: occupationSelection,
      summerJobs,
      youthSummerJobs,
    } as URLParams);
  };

  const handleKeywordChange = ({ target: { value } }: { target: { value: string } }) => setKeyword(value);

  const handleOccupationsChange = (option: OptionType[]) => setOccupationFilter(option);

  return (
    <form onSubmit={handleSubmit}>
      <fieldset>
        <TextInput id={SearchComponents.KEYWORD} label='text' onChange={handleKeywordChange} value={keyword} />
      </fieldset>
      <fieldset>
        <Select
          aria-labelledby=''
          clearButtonAriaLabel=''
          selectedItemRemoveButtonAriaLabel=''
          placeholder=''
          multiselect
          label={Drupal.t('Ammattikunta', { context: 'Occupations filter label' })}
          helper={Drupal.t('ammattikunta - a18n', { context: 'Occupations filter helper' })}
          // @ts-ignore
          options={occupationsOptions}
          value={occupationSelection}
          id={SearchComponents.OCCUPATIONS}
          onChange={handleOccupationsChange}
        />
      </fieldset>
      <fieldset>
        <legend>{Drupal.t('Show only')}</legend>
        <Checkbox
          label={Drupal.t(CONTINUOUS.value)}
          id={SearchComponents.CONTINUOUS}
          onClick={() => setContinuous(!continuous)}
          checked={continuous}
          name={Drupal.t(CONTINUOUS.value)}
        />
        <Checkbox
          label={Drupal.t(INTERNSHIPS.value)}
          id={SearchComponents.INTERSHIPS}
          onClick={() => setInternship(!internship)}
          checked={internship}
          name={Drupal.t(INTERNSHIPS.value)}
        />
        <Checkbox
          label={SUMMER_JOBS.value}
          id={SearchComponents.SUMMER_JOBS}
          onClick={() => setSummerJobs(!summerJobs)}
          checked={summerJobs}
          name={SUMMER_JOBS.value}
        />
        <Checkbox
          label={YOUTH_SUMMER_JOBS.value}
          id={SearchComponents.YOUTH_SUMMER_JOBS}
          onClick={() => setYouthSummerJobs(!youthSummerJobs)}
          checked={youthSummerJobs}
          name={YOUTH_SUMMER_JOBS.value}
        />
      </fieldset>
      <SelectionsContainer />
      <Button type='submit'>{Drupal.t('Submit', { context: 'Rekry Search Submit button' })}</Button>
    </form>
  );
};

export default FormContainer;
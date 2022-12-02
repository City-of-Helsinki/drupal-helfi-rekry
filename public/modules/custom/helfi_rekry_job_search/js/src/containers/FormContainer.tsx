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
import type OptionType from '../types/OptionType';
import type URLParams from '../types/URLParams';

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
    // let defaultOccupation = undefined;
    // if (urlParams.occupation) {
    //   // defaultOccupation = occupationsOptions.find(({ value }) => value === urlParams.occupation);
    //   setOccupationFilter(defaultOccupation as OptionType);
    // }
    setContinuous(!!urlParams?.[SearchComponents.CONTINUOUS]);
    setInternship(!!urlParams?.[SearchComponents.INTERSHIPS]);
    setSummerJobs(!!urlParams?.[SearchComponents.SUMMER_JOBS]);
    setYouthSummerJobs(!!urlParams?.[SearchComponents.YOUTH_SUMMER_JOBS]);
  }, []);

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setUrlParams({
      ...urlParams,
      keyword: keyword,
      // [SearchComponents.OCCUPATIONS]: occupationFilter?.value,
      [SearchComponents.CONTINUOUS]: continuous,
      [SearchComponents.INTERSHIPS]: internship,
      [SearchComponents.SUMMER_JOBS]: summerJobs,
      [SearchComponents.YOUTH_SUMMER_JOBS]: youthSummerJobs,
    } as URLParams);
  };

  const handleKeywordChange = ({ target: { value } }: { target: { value: string } }) => setKeyword(value);

  const handleOccupationsChange = (option: OptionType | OptionType[]) => setOccupationFilter(option);

  return (
    <form onSubmit={handleSubmit}>
      <fieldset>
        <TextInput id={SearchComponents.KEYWORD} label='text' onChange={handleKeywordChange} value={keyword} />
      </fieldset>
      <fieldset>
        <Select
          multiselect
          label={Drupal.t('Ammattikunta', { context: 'Occupations filter label' })}
          helper={Drupal.t('ammattikunta - a18n', { context: 'Occupations filter helper' })}
          options={occupationsOptions}
          value={occupationSelection}
          id={SearchComponents.OCCUPATIONS}
          onChange={handleOccupationsChange}
        />
      </fieldset>
      <fieldset>
        <legend>{Drupal.t('Show only')}</legend>
        <Checkbox
          label={Drupal.t('Continuous')}
          id={SearchComponents.CONTINUOUS}
          onClick={() => setContinuous(!continuous)}
          checked={continuous}
        />
        <Checkbox
          label={Drupal.t('Internships')}
          id={SearchComponents.INTERSHIPS}
          onClick={() => setInternship(!internship)}
          checked={internship}
        />
        <Checkbox
          label={Drupal.t('Summer jobs')}
          id={SearchComponents.SUMMER_JOBS}
          onClick={() => setSummerJobs(!summerJobs)}
          checked={summerJobs}
        />
        <Checkbox
          label={Drupal.t('Summer jobs for youth')}
          id={SearchComponents.YOUTH_SUMMER_JOBS}
          onClick={() => setYouthSummerJobs(!youthSummerJobs)}
          checked={youthSummerJobs}
        />
      </fieldset>
      <Button type='submit'>{Drupal.t('Submit', { context: 'Rekry Search Submit button' })}</Button>
    </form>
  );
};

export default FormContainer;

import { Button, Checkbox, Select, TextInput } from 'hds-react';
import { useAtom, useAtomValue } from 'jotai';
import { useUpdateAtom } from 'jotai/utils';
import React, { useEffect } from 'react';

import SearchComponents from '../enum/SearchComponents';
import {
  continuousAtom,
  internshipAtom,
  keywordAtom,
  summerJobsAtom,
  taskAreasAtom,
  taskAreasSelectionAtom,
  urlUpdateAtom,
  youthSummerJobsAtom,
} from '../store';
import { urlAtom } from '../store';
import { CONTINUOUS, INTERNSHIPS, SUMMER_JOBS, YOUTH_SUMMER_JOBS } from '../translations';
import type OptionType from '../types/OptionType';
import SelectionsContainer from './SelectionsContainer';

const FormContainer = () => {
  const formAction = drupalSettings?.helfi_rekry_job_search?.results_page_path || '';
  const [continuous, setContinuous] = useAtom(continuousAtom);
  const [internship, setInternship] = useAtom(internshipAtom);
  const [summerJobs, setSummerJobs] = useAtom(summerJobsAtom);
  const [youthSummerJobs, setYouthSummerJobs] = useAtom(youthSummerJobsAtom);
  const [keyword, setKeyword] = useAtom(keywordAtom);
  const urlParams = useAtomValue(urlAtom);
  const setUrlParams = useUpdateAtom(urlUpdateAtom);
  const [taskAreaSelection, setTaskAreaFilter] = useAtom(taskAreasSelectionAtom);
  const taskAreasOptions = useAtomValue(taskAreasAtom);

  const transformTaskAreas = (taskAreas: string[] | undefined = []) => {
    const transformedOptions: OptionType[] = [];

    taskAreas.forEach((taskArea: string) => {
      const matchedOption = taskAreasOptions.find((option: OptionType) => option.value === taskArea);

      if (matchedOption) {
        transformedOptions.push({
          label: matchedOption.label,
          value: matchedOption.value,
        });
      }
    });

    return transformedOptions;
  };

  // Set form control values from url parameters on load
  useEffect(() => {
    setKeyword(urlParams?.keyword || '');
    setTaskAreaFilter(transformTaskAreas(urlParams?.task_areas));
    setContinuous(!!urlParams?.continuous);
    setInternship(!!urlParams?.internship);
    setSummerJobs(!!urlParams?.summerJobs);
    setYouthSummerJobs(!!urlParams?.youthSummerJobs);
  }, []);

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    if (formAction.length) {
      return true;
    }

    event.preventDefault();
    setUrlParams({
      keyword,
      continuous,
      internship,
      task_areas: taskAreaSelection.map((selection: OptionType) => selection.value),
      summerJobs,
      youthSummerJobs,
    });
  };

  const handleKeywordChange = ({ target: { value } }: { target: { value: string } }) => setKeyword(value);

  const handleTaskAreasChange = (option: OptionType[]) => setTaskAreaFilter(option);
  const taskAreaInputValue = taskAreaSelection.map((option: OptionType) => option.value);

  return (
    <form className='job-search-form' onSubmit={handleSubmit} action={formAction}>
      <h2 className='job-search-form__title'>
        {Drupal.t('Filter search', {}, { context: 'Recruitment search title' })}
      </h2>
      <TextInput
        className='job-search-form__filter'
        id={SearchComponents.KEYWORD}
        label={Drupal.t('Keyword', { context: 'Search keyword label' })}
        onChange={handleKeywordChange}
        value={keyword}
        placeholder={Drupal.t('Eg. title, office, department', { context: 'Search keyword placeholder' })}
      />
      <div className='job-search-form__filter'>
        <Select
          clearButtonAriaLabel=''
          className='job-search-form__dropdown'
          selectedItemRemoveButtonAriaLabel=''
          placeholder={Drupal.t('All task areas', { context: 'Task areas filter placeholder' })}
          multiselect
          label={Drupal.t('Task area', { context: 'Task areas filter label' })}
          // @ts-ignore
          options={taskAreasOptions}
          value={taskAreaSelection}
          id={SearchComponents.TASK_AREAS}
          onChange={handleTaskAreasChange}
        />
        {formAction && (
          <select
            aria-hidden
            multiple
            value={taskAreaInputValue}
            name={SearchComponents.TASK_AREAS}
            style={{ display: 'none' }}
          >
            {taskAreaInputValue.map((value: string) => (
              <option key={value} value={value} selected />
            ))}
          </select>
        )}
      </div>
      <fieldset className='job-search-form__checkboxes'>
        <legend className='job-search-form__checkboxes-legend'>{Drupal.t('Show only')}</legend>
        <Checkbox
          className='job-search-form__checkbox'
          label={Drupal.t(CONTINUOUS.value)}
          id={SearchComponents.CONTINUOUS}
          onClick={() => setContinuous(!continuous)}
          checked={continuous}
          name={SearchComponents.CONTINUOUS}
          value={continuous.toString()}
        />
        <Checkbox
          className='job-search-form__checkbox'
          label={Drupal.t(INTERNSHIPS.value)}
          id={SearchComponents.INTERSHIPS}
          onClick={() => setInternship(!internship)}
          checked={internship}
          name={SearchComponents.INTERSHIPS}
          value={internship.toString()}
        />
        <Checkbox
          className='job-search-form__checkbox'
          label={SUMMER_JOBS.value}
          id={SearchComponents.SUMMER_JOBS}
          onClick={() => setSummerJobs(!summerJobs)}
          checked={summerJobs}
          name={SearchComponents.SUMMER_JOBS}
          value={summerJobs.toString()}
        />
        <Checkbox
          className='job-search-form__checkbox'
          label={YOUTH_SUMMER_JOBS.value}
          id={SearchComponents.YOUTH_SUMMER_JOBS}
          onClick={() => setYouthSummerJobs(!youthSummerJobs)}
          checked={youthSummerJobs}
          name={SearchComponents.YOUTH_SUMMER_JOBS}
          value={youthSummerJobs.toString()}
        />
      </fieldset>
      <Button className='hds-button hds-button--primary job-search-form__submit-button' type='submit'>
        {Drupal.t('Submit', { context: 'Rekry Search Submit button' })}
      </Button>
      <SelectionsContainer />
    </form>
  );
};

export default FormContainer;

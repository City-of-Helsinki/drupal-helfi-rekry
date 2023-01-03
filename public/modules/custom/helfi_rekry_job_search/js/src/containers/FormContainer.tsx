import { Button, Checkbox, Select, TextInput } from 'hds-react';
import { useAtom, useAtomValue } from 'jotai';
import { useUpdateAtom } from 'jotai/utils';
import React, { Fragment, useEffect } from 'react';

import SearchComponents from '../enum/SearchComponents';
import { getInitialLanguage } from '../helpers/Language';
import { transformDropdownsValues } from '../helpers/Params';
import {
  continuousAtom,
  employmentAtom,
  employmentSelectionAtom,
  internshipAtom,
  keywordAtom,
  languageSelectionAtom,
  languagesAtom,
  summerJobsAtom,
  taskAreasAtom,
  taskAreasSelectionAtom,
  urlUpdateAtom,
  youthSummerJobsAtom,
} from '../store';
import { urlAtom } from '../store';
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
  const employmentOptions = useAtomValue(employmentAtom);
  const [employmentSelection, setEmploymentFilter] = useAtom(employmentSelectionAtom);
  const languagesOptions = useAtomValue(languagesAtom);
  const [languageSelection, setLanguageFilter] = useAtom(languageSelectionAtom);

  // Set form control values from url parameters on load
  useEffect(() => {
    setKeyword(urlParams?.keyword?.toString() || '');
    setTaskAreaFilter(transformDropdownsValues(urlParams?.task_areas, taskAreasOptions));
    setEmploymentFilter(transformDropdownsValues(urlParams?.employment, employmentOptions));
    setContinuous(!!urlParams?.continuous);
    setInternship(!!urlParams?.internship);
    setSummerJobs(!!urlParams?.summer_jobs);
    setYouthSummerJobs(!!urlParams?.youth_summer_jobs);
    setLanguageFilter(getInitialLanguage(urlParams?.language, languagesOptions));
  }, []);

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    if (formAction.length) {
      return true;
    }

    event.preventDefault();
    setUrlParams({
      employment: employmentSelection.map((selection: OptionType) => selection.value),
      keyword,
      language: languageSelection?.value,
      continuous,
      internship,
      task_areas: taskAreaSelection.map((selection: OptionType) => selection.value),
      summer_jobs: summerJobs,
      youth_summer_jobs: youthSummerJobs,
    });
  };

  const handleKeywordChange = ({ target: { value } }: { target: { value: string } }) => setKeyword(value);

  // Input values for native elements
  const taskAreaInputValue = taskAreaSelection.map((option: OptionType) => option.value);
  const employmentInputValue = employmentSelection.map((option: OptionType) => option.value);

  const isFullSearch = !drupalSettings?.helfi_rekry_job_search?.results_page_path;

  // @todo enable checkboxes once https://helsinkisolutionoffice.atlassian.net/browse/UHF-7763 is done
  const showCheckboxes = false;

  return (
    <form className='job-search-form' onSubmit={handleSubmit} action={formAction}>
      <TextInput
        className='job-search-form__filter'
        id={SearchComponents.KEYWORD}
        label={Drupal.t('Keyword', { context: 'Search keyword label' })}
        name={SearchComponents.KEYWORD}
        onChange={handleKeywordChange}
        value={keyword}
        placeholder={Drupal.t(
          'Eg. title, location, department',
          {},
          { context: 'HELfi Rekry job search keyword placeholder' }
        )}
      />
      <div className='job-search-form__dropdowns'>
        <div className='job-search-form__dropdowns__upper'>
          <div className='job-search-form__filter job-search-form__dropdown--upper'>
            <Select
              clearButtonAriaLabel={Drupal.t('Clear selection', {}, { context: 'Job search clear button aria label' })}
              className='job-search-form__dropdown'
              selectedItemRemoveButtonAriaLabel={Drupal.t(
                'Remove item',
                {},
                { context: 'Job search remove item aria label' }
              )}
              placeholder={Drupal.t('All fields', {}, { context: 'Task areas filter placeholder' })}
              multiselect
              label={Drupal.t('Task area', {}, { context: 'Task areas filter label' })}
              // @ts-ignore
              options={taskAreasOptions}
              value={taskAreaSelection}
              id={SearchComponents.TASK_AREAS}
              onChange={setTaskAreaFilter}
            />
          </div>
          <div className='job-search-form__filter job-search-form__dropdown--upper'>
            <Select
              clearButtonAriaLabel={Drupal.t('Clear selection', {}, { context: 'Job search clear button aria label' })}
              className='job-search-form__dropdown'
              selectedItemRemoveButtonAriaLabel={Drupal.t(
                'Remove item',
                {},
                { context: 'Job search remove item aria label' }
              )}
              placeholder={Drupal.t(
                'All employment relationship options',
                {},
                { context: 'Employment filter placeholder' }
              )}
              multiselect
              label={Drupal.t('Type of employment relationship', {}, { context: 'Employment filter label' })}
              // @ts-ignore
              options={employmentOptions}
              value={employmentSelection}
              id={SearchComponents.TASK_AREAS}
              onChange={setEmploymentFilter}
            />
          </div>
        </div>
        {/** Hidden select elements to enable native form functions */}
        {formAction && (
          <Fragment>
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
            <select
              aria-hidden
              multiple
              value={employmentInputValue}
              name={SearchComponents.EMPLOYMENT}
              style={{ display: 'none' }}
            >
              {employmentInputValue.map((value: string) => (
                <option key={value} value={value} selected />
              ))}
            </select>
          </Fragment>
        )}
      </div>
      {isFullSearch && (
        <div className='job-search-form__dropdowns'>
          <div className='job-search-form__dropdowns__lower'>
            <div className='job-search-form__filter job-search-form__dropdown--upper'>
              <Select
                clearButtonAriaLabel={Drupal.t(
                  'Clear selection',
                  {},
                  { context: 'Job search clear button aria label' }
                )}
                className='job-search-form__dropdown'
                clearable
                selectedItemRemoveButtonAriaLabel={Drupal.t(
                  'Remove item',
                  {},
                  { context: 'Job search remove item aria label' }
                )}
                placeholder={Drupal.t('All languages', {}, { context: 'Language placeholder' })}
                label={Drupal.t('Language', {}, { context: 'Language filter label' })}
                // @ts-ignore
                options={languagesOptions}
                value={languageSelection}
                id={SearchComponents.LANGUAGE}
                onChange={setLanguageFilter}
              />
            </div>
          </div>
        </div>
      )}
      {isFullSearch && showCheckboxes && (
        <fieldset className='job-search-form__checkboxes'>
          <legend className='job-search-form__checkboxes-legend'>
            {Drupal.t('Filters', {}, { context: 'Checkbox filters heading' })}
          </legend>
          <Checkbox
            className='job-search-form__checkbox'
            label={Drupal.t('Open-ended vacancies', {}, { context: 'Job search' })}
            id={SearchComponents.CONTINUOUS}
            onClick={() => setContinuous(!continuous)}
            checked={continuous}
            name={SearchComponents.CONTINUOUS}
            value={continuous.toString()}
          />
          <Checkbox
            className='job-search-form__checkbox'
            label={Drupal.t('Practical training', {}, { context: 'Job search' })}
            id={SearchComponents.INTERNSHIPS}
            onClick={() => setInternship(!internship)}
            checked={internship}
            name={SearchComponents.INTERNSHIPS}
            value={internship.toString()}
          />
          <Checkbox
            className='job-search-form__checkbox'
            label={Drupal.t('summer jobs and substitute vacancies', {}, { context: 'Job search' })}
            id={SearchComponents.SUMMER_JOBS}
            onClick={() => setSummerJobs(!summerJobs)}
            checked={summerJobs}
            name={SearchComponents.SUMMER_JOBS}
            value={summerJobs.toString()}
          />
          <Checkbox
            className='job-search-form__checkbox'
            label={Drupal.t('Summer jobs for young people', {}, { context: 'Job search' })}
            id={SearchComponents.YOUTH_SUMMER_JOBS}
            onClick={() => setYouthSummerJobs(!youthSummerJobs)}
            checked={youthSummerJobs}
            name={SearchComponents.YOUTH_SUMMER_JOBS}
            value={youthSummerJobs.toString()}
          />
        </fieldset>
      )}
      <Button className='hds-button hds-button--primary job-search-form__submit-button' type='submit'>
        {Drupal.t('Search')}
      </Button>
      <SelectionsContainer />
    </form>
  );
};

export default FormContainer;

import RadioOptions from '../enum/RadioOptions';

const getRadioFilter = (value: string) => {
  switch (value) {
    case RadioOptions.CONTINUOUS:
      return {
        term: {
          field_job_duration: '2.1.2023',
        },
      };
    default:
      return;
  }
};

export default getRadioFilter;

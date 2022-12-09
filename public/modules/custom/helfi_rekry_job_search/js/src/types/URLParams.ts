import OptionType from './OptionType';

type URLParams = {
  continuous?: boolean;
  internship?: boolean;
  keyword?: string;
  task_areas?: OptionType[];
  page?: string;
  summerJobs?: boolean;
  youthSummerJobs?: boolean;
};

export default URLParams;

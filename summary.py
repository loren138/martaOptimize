#n[6]:

import pandas as pd

df = pd.read_csv("train_ridership_one_month.csv")

df.head()


# In[64]:

# Below code removes unwanted passenger activity codes for parking
valid_codes = [1, 9, 10, 11, 12]
df = df[df.use_type != 13 ]
df = df[df.use_type != 14 ]
clean_df = df[df.use_type != 15 ]
cols = ["transit_day", "transit_time", "station_id", "use_type", "serial_number"]
clean_df = clean_df[cols]


# In[13]:

#seperates entries and exits into two tables
entry_values = [1, 9, 12]
exit_values = [10, 11]

entry_df = clean_df.loc[df['use_type'].isin(entry_values)]
entry_df.rename(columns = {'transit_time':'entry_time'}, inplace = True)
exit_df = clean_df.loc[df['use_type'].isin(exit_values)]
exit_df.rename(columns = {'transit_time':'exit_time'}, inplace = True)
entry_df.head()


# In[60]:

#creates table of entries by day and station and then exports to csv
entry_df = entry_df.drop(['entry_time','use_type'],axis=1)
station_entry = entry_df.groupby(['station_id', 'transit_day']).count()
station_entry.rename(columns = {'serial_number':'traveler_count'}, inplace = True)
station_entry.to_csv('station_entry.csv')


# In[61]:

#creates table of exits by day and station and then exports to csv
exit_df = exit_df.drop(['exit_time','use_type'],axis=1)
station_exit = exit_df.groupby(['station_id', 'transit_day']).count()
station_exit.rename(columns = {'serial_number':'traveler_count'}, inplace = True)
station_exit.to_csv('station_exit.csv')


# In[ ]:





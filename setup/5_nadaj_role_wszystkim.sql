INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`)
select 
1, null, u.samaccountname
from ad_user u
group by u.samaccountname; 





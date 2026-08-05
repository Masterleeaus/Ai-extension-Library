import 'package:qrpay/backend/model/pusher/pusher_beams_model.dart';

import '../../utils/api_method.dart';
import '../../utils/custom_snackbar.dart';
import '../api_endpoint.dart';

class PusherApiServices {
  // Get Pusher Beams Auth
  static Future<PusherBeamsModel?> getPusherBeamsAuth(String userId) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false).get(
        "${ApiEndpoint.pusherBeamsAuthURL}$userId",
        code: 200,
      );
      if (mapResponse != null) {
        PusherBeamsModel result = PusherBeamsModel.fromJson(mapResponse);

        return result;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from Pusher Beams Auth Api service ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }
}

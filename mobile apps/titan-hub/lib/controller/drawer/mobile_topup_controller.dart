import 'package:get/get.dart';

import '../../backend/model/transaction_log/transaction_log_model.dart';
import '../../backend/services/api_services.dart';
import '../../backend/utils/logger.dart';

final log = logger(MobileTopLogUpController);

class MobileTopLogUpController extends GetxController{


  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late TransactionLogModel _transactioData;
  TransactionLogModel get transactioData =>
      _transactioData;


  @override
  void onInit() {
    super.onInit();
    getTransactionData();
  }

  // --------------------------- Api function ----------------------------------
  Future<TransactionLogModel> getTransactionData() async {
    _isLoading.value = true;
    update();

    await ApiServices.getTransactionLogAPi(type: "/mobile-topup").then((value) {
      _transactioData = value!;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _transactioData;
  }


}